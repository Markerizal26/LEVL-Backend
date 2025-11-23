<?php

namespace Modules\Auth\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Interfaces\AuthRepositoryInterface;
use Modules\Auth\Interfaces\AuthServiceInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Support\TokenPairDTO;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\CourseAdmin;
use Tymon\JWTAuth\JWTAuth;

class AuthService implements AuthServiceInterface
{
  public function __construct(
    private readonly AuthRepositoryInterface $authRepository,
    private readonly JWTAuth $jwt,
    private readonly EmailVerificationService $emailVerification,
    private readonly LoginThrottlingService $throttle,
  ) {}

  public function register(array $validated, string $ip, ?string $userAgent): array
  {
    $validated["password"] = Hash::make($validated["password"]);
    $user = $this->authRepository->createUser($validated);

    $user->assignRole("Student");

    $token = $this->jwt->fromUser($user);

    $deviceId = hash("sha256", ($ip ?? "") . ($userAgent ?? "") . $user->id);
    $refresh = $this->authRepository->createRefreshToken(
      userId: $user->id,
      ip: $ip,
      userAgent: $userAgent,
      deviceId: $deviceId,
    );

    $pair = new TokenPairDTO(
      accessToken: $token,
      expiresIn: $this->jwt->factory()->getTTL() * 60,
      refreshToken: $refresh->getAttribute("plain_token"),
    );

    $verificationUuid = $this->emailVerification->sendVerificationLink($user);

    $userArray = $user->toArray();
    $userArray["roles"] = $user->getRoleNames()->values();

    $response = ["user" => $userArray] + $pair->toArray();

    if ($verificationUuid) {
      $response["verification_uuid"] = $verificationUuid;
    }

    return $response;
  }

  public function login(string $login, string $password, string $ip, ?string $userAgent): array
  {
    $this->throttle->ensureNotLocked($login);
    if ($this->throttle->tooManyAttempts($login, $ip)) {
      $retryAfter = $this->throttle->getRetryAfterSeconds($login, $ip);
      $cfg = $this->throttle->getRateLimitConfig();
      $m = intdiv($retryAfter, 60);
      $s = $retryAfter % 60;
      $retryIn = $m > 0 ? $m . " menit" . ($s > 0 ? " " . $s . " detik" : "") : $s . " detik";
      throw ValidationException::withMessages([
        "login" => "Terlalu banyak percobaan login. Maksimal {$cfg["max"]} kali dalam {$cfg["decay"]} menit. Coba lagi dalam {$retryIn}.",
      ]);
    }

    $user = $this->authRepository->findByLogin($login);
    if (!$user || !Hash::check($password, $user->password)) {
      $this->throttle->hitAttempt($login, $ip);
      $this->throttle->recordFailureAndMaybeLock($login);
      throw ValidationException::withMessages([
        "login" => "Username/email atau password salah.",
      ]);
    }

    $roles = $user->getRoleNames();
    $isPrivileged = $roles->contains(fn($r) => in_array($r, ["Superadmin", "Admin", "Instructor"]));

    // Auto-verify privileged users (Admin, Superadmin, Instructor) on first login
    $wasAutoVerified = false;
    if ($isPrivileged && ($user->status === "pending" || $user->email_verified_at === null)) {
      $user->email_verified_at = now();
      $user->status = "active";
      $user->save();
      $user->refresh(); // Refresh to get updated attributes
      $wasAutoVerified = true;
    }

    $token = $this->jwt->fromUser($user);

    $deviceId = hash("sha256", ($ip ?? "") . ($userAgent ?? "") . $user->id);
    $refresh = $this->authRepository->createRefreshToken(
      userId: $user->id,
      ip: $ip,
      userAgent: $userAgent,
      deviceId: $deviceId,
    );

    $pair = new TokenPairDTO(
      accessToken: $token,
      expiresIn: $this->jwt->factory()->getTTL() * 60,
      refreshToken: $refresh->getAttribute("plain_token"),
    );

    $this->throttle->clearAttempts($login, $ip);

    $userArray = $user->toArray();
    $userArray["roles"] = $user->getRoleNames()->values();

    $response = ["user" => $userArray] + $pair->toArray();

    if ($user->status === "pending" && $user->email_verified_at === null && !$isPrivileged) {
      $verificationUuid = $this->emailVerification->sendVerificationLink($user);
      $response["status"] = "pending";
      $response["message"] = "Akun Anda belum aktif. Silakan verifikasi email terlebih dahulu.";
      if ($verificationUuid) {
        $response["verification_uuid"] = $verificationUuid;
      }
    } elseif ($user->status === "inactive") {
      $response["status"] = "inactive";
      $response["message"] = "Akun Anda tidak aktif. Hubungi administrator.";
    } elseif ($user->status === "banned") {
      $response["status"] = "banned";
      $response["message"] = "Akun Anda telah dibanned. Hubungi administrator.";
    } elseif ($wasAutoVerified) {
      $response["message"] = "Login berhasil. Akun Anda telah otomatis diverifikasi.";
    }

    return $response;
  }

  public function refresh(string $refreshToken, string $ip, ?string $userAgent): array
  {
    $record = $this->authRepository->findValidRefreshRecord($refreshToken);
    if (!$record) {
      throw ValidationException::withMessages([
        "refresh_token" => "Refresh token tidak valid atau kadaluarsa.",
      ]);
    }

    $user = $record->user;
    if (!$user) {
      throw ValidationException::withMessages([
        "refresh_token" => "User tidak ditemukan untuk refresh token ini.",
      ]);
    }

    if ($user->status !== "active") {
      throw ValidationException::withMessages([
        "refresh_token" => "Akun tidak aktif.",
      ]);
    }

    if ($record->isReplaced()) {
      $chain = $this->authRepository->findReplacedTokenChain($record->id);
      $deviceIds = collect($chain)->pluck("device_id")->unique()->filter()->toArray();

      foreach ($deviceIds as $deviceId) {
        $this->authRepository->revokeAllUserRefreshTokensByDevice($user->id, $deviceId);
      }

      throw ValidationException::withMessages([
        "refresh_token" =>
          "Refresh token telah digunakan sebelumnya. Semua sesi perangkat telah dicabut karena potensi keamanan.",
      ]);
    }

    $deviceId = $record->device_id ?? hash("sha256", ($ip ?? "") . ($userAgent ?? "") . $user->id);

    $newRefresh = $this->authRepository->createRefreshToken(
      userId: $user->id,
      ip: $ip,
      userAgent: $userAgent,
      deviceId: $deviceId,
    );

    $this->authRepository->markTokenAsReplaced($record->id, $newRefresh->id);

    $record->update([
      "last_used_at" => now(),
      "idle_expires_at" => now()->addDays(14),
    ]);

    $accessToken = $this->jwt->fromUser($user);

    return [
      "access_token" => $accessToken,
      "expires_in" => $this->jwt->factory()->getTTL() * 60,
      "refresh_token" => $newRefresh->getAttribute("plain_token"),
    ];
  }

  public function logout(User $user, string $currentJwt, ?string $refreshToken = null): void
  {
    $this->jwt->setToken($currentJwt)->invalidate();
    if ($refreshToken) {
      $this->authRepository->revokeRefreshToken($refreshToken, $user->id);
    }
  }

  public function me(User $user): User
  {
    return $user;
  }

  public function createInstructor(array $validated): array
  {
    $passwordPlain = $this->generatePasswordFromNameEmail(
      $validated["name"] ?? "",
      $validated["email"] ?? "",
    );
    $validated["password"] = Hash::make($passwordPlain);
    $user = $this->authRepository->createUser($validated);
    $user->assignRole("Instructor");

    $this->sendGeneratedPasswordEmail($user, $passwordPlain);

    $userArray = $user->toArray();
    $userArray["roles"] = $user->getRoleNames()->values();

    return ["user" => $userArray];
  }

  public function createAdmin(array $validated): array
  {
    $passwordPlain = $this->generatePasswordFromNameEmail(
      $validated["name"] ?? "",
      $validated["email"] ?? "",
    );
    $validated["password"] = Hash::make($passwordPlain);
    $user = $this->authRepository->createUser($validated);
    $user->assignRole("Admin");

    $this->sendGeneratedPasswordEmail($user, $passwordPlain);

    $userArray = $user->toArray();
    $userArray["roles"] = $user->getRoleNames()->values();

    return ["user" => $userArray];
  }

  public function createSuperAdmin(array $validated): array
  {
    $passwordPlain = $this->generatePasswordFromNameEmail(
      $validated["name"] ?? "",
      $validated["email"] ?? "",
    );
    $validated["password"] = Hash::make($passwordPlain);
    $user = $this->authRepository->createUser($validated);
    $user->assignRole("Superadmin");

    $this->sendGeneratedPasswordEmail($user, $passwordPlain);

    $userArray = $user->toArray();
    $userArray["roles"] = $user->getRoleNames()->values();

    return ["user" => $userArray];
  }

  public function listUsers(User $authUser, array $params, int $perPage = 15): LengthAwarePaginator
  {
    $perPage = max(1, $perPage);
    $query = $this->buildUserListQuery($params);

    $isSuperadmin = $authUser->hasRole("Superadmin");
    $isAdmin = $authUser->hasRole("Admin");

    if ($isAdmin && !$isSuperadmin) {
      $this->applyAdminUserScope($query, $authUser);
    }

    $sort = (string) ($params["sort"] ?? "-created_at");
    $direction = "asc";
    $field = $sort;
    if (str_starts_with($sort, "-")) {
      $direction = "desc";
      $field = substr($sort, 1);
    }
    $allowedSorts = ["name", "email", "username", "status", "created_at"];
    if (!in_array($field, $allowedSorts, true)) {
      $field = "created_at";
      $direction = "desc";
    }
    $query->orderBy($field, $direction);

    $paginator = $query->paginate($perPage)->appends($params);
    $paginator->getCollection()->transform(fn($u) => $this->transformUserForList($u));

    return $paginator;
  }

  /**
   * @throws AuthorizationException
   */
  public function showUser(User $authUser, User $target): array
  {
    $isSuperadmin = $authUser->hasRole("Superadmin");
    $isAdmin = $authUser->hasRole("Admin");

    if (!$isSuperadmin && !$isAdmin) {
      throw new AuthorizationException("Tidak terotorisasi.");
    }

    if ($isAdmin && !$isSuperadmin && !$this->adminCanSeeUser($authUser, $target)) {
      throw new AuthorizationException("Anda tidak memiliki akses untuk melihat pengguna ini.");
    }

    return $this->formatUserDetails($target);
  }

  /**
   * @throws ValidationException
   */
  public function updateUserStatus(User $user, string $status): User
  {
    if ($status === "pending") {
      throw ValidationException::withMessages([
        "status" => "Mengubah status ke pending tidak diperbolehkan.",
      ]);
    }

    $user->status = $status;
    $user->save();

    return $user->fresh();
  }

  private function buildUserListQuery(array $params): Builder
  {
    $query = User::query()
      ->select([
        "id",
        "name",
        "email",
        "username",
        "avatar_path",
        "status",
        "created_at",
        "email_verified_at",
      ])
      ->with("roles");

    $search = trim((string) ($params["search"] ?? ""));
    if ($search !== "") {
      $query->where(function ($sub) use ($search) {
        $sub
          ->where("name", "like", "%" . $search . "%")
          ->orWhere("email", "like", "%" . $search . "%")
          ->orWhere("username", "like", "%" . $search . "%");
      });
    }

    $status = $params["filter"]["status"] ?? null;
    if (is_string($status) && $status !== "") {
      $statuses = array_values(array_filter(array_map("trim", explode(",", $status))));
      if (!empty($statuses)) {
        $query->whereIn("status", $statuses);
      }
    }

    $email = $params["filter"]["email"] ?? null;
    if (is_string($email) && $email !== "") {
      $query->where("email", "like", "%" . $email . "%");
    }

    $role = $params["filter"]["role"] ?? null;
    if (is_string($role) && $role !== "") {
      $roles = array_values(array_filter(array_map("trim", explode(",", $role))));
      if (!empty($roles)) {
        $query->whereHas("roles", function ($q2) use ($roles) {
          $q2->whereIn("name", $roles);
        });
      }
    }

    $createdFrom = $params["filter"]["created_from"] ?? null;
    if (is_string($createdFrom) && $createdFrom !== "") {
      $query->whereDate("created_at", ">=", $createdFrom);
    }

    $createdTo = $params["filter"]["created_to"] ?? null;
    if (is_string($createdTo) && $createdTo !== "") {
      $query->whereDate("created_at", "<=", $createdTo);
    }

    return $query;
  }

  private function transformUserForList(User $user): array
  {
    return [
      "id" => $user->id,
      "name" => $user->name,
      "email" => $user->email,
      "username" => $user->username,
      "avatar" => $user->avatar_path ? asset("storage/" . $user->avatar_path) : null,
      "status" => $user->status,
      "created_at" => $user->created_at?->toISOString(),
      "email_verified_at" => $user->email_verified_at?->toISOString(),
      "roles" => method_exists($user, "getRoleNames")
        ? $user->getRoleNames()->values()
        : $user->roles?->pluck("name")->values() ?? [],
    ];
  }

  private function formatUserDetails(User $user): array
  {
    return [
      "id" => $user->id,
      "name" => $user->name,
      "email" => $user->email,
      "username" => $user->username,
      "avatar" => $user->avatar_path ? asset("storage/" . $user->avatar_path) : null,
      "status" => $user->status,
      "created_at" => $user->created_at?->toISOString(),
      "email_verified_at" => $user->email_verified_at?->toISOString(),
      "roles" => method_exists($user, "getRoleNames")
        ? $user->getRoleNames()->values()
        : $user->roles?->pluck("name")->values() ?? [],
    ];
  }

  private function applyAdminUserScope(Builder $query, User $admin): void
  {
    $managedCourseIds = $this->managedCourseIds($admin);

    $query->where(function (Builder $visibility) use ($managedCourseIds) {
      $visibility
        ->where(function (Builder $studentScope) use ($managedCourseIds) {
          if ($managedCourseIds->isEmpty()) {
            $studentScope->whereRaw("0 = 1");

            return;
          }

          $studentScope
            ->whereHas("roles", function ($roleQuery) {
              $roleQuery->where("name", "Student");
            })
            ->whereHas("enrollments", function ($enrollmentQuery) use ($managedCourseIds) {
              $enrollmentQuery->whereIn("course_id", $managedCourseIds);
            });
        })
        ->orWhere(function (Builder $adminScope) {
          $adminScope
            ->whereHas("roles", function ($roleQuery) {
              $roleQuery->where("name", "Admin");
            })
            ->whereDoesntHave("roles", function ($roleQuery) {
              $roleQuery->where("name", "Superadmin");
            });
        });
    });
  }

  private function adminCanSeeUser(User $admin, User $target): bool
  {
    if ($target->hasRole("Superadmin")) {
      return false;
    }

    if ($target->hasRole("Admin")) {
      return true;
    }

    if (!$target->hasRole("Student")) {
      return false;
    }

    $managedCourseIds = $this->managedCourseIds($admin);
    if ($managedCourseIds->isEmpty()) {
      return false;
    }

    return Enrollment::query()
      ->where("user_id", $target->id)
      ->whereIn("course_id", $managedCourseIds)
      ->exists();
  }

  private function managedCourseIds(User $admin): Collection
  {
    return CourseAdmin::query()
      ->where("user_id", $admin->id)
      ->pluck("course_id")
      ->unique()
      ->values();
  }

  private function generatePasswordFromNameEmail(string $name, string $email): string
  {
    $length = 14;
    $upper = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    $lower = "abcdefghijkmnpqrstuvwxyz";
    $numbers = "23456789";
    $symbols = '!@#$%^&*()-_=+[]{}';

    $passwordChars = [];
    $passwordChars[] = $upper[random_int(0, strlen($upper) - 1)];
    $passwordChars[] = $lower[random_int(0, strlen($lower) - 1)];
    $passwordChars[] = $numbers[random_int(0, strlen($numbers) - 1)];
    $passwordChars[] = $symbols[random_int(0, strlen($symbols) - 1)];

    $all = $upper . $lower . $numbers . $symbols;
    for ($i = count($passwordChars); $i < $length; $i++) {
      $passwordChars[] = $all[random_int(0, strlen($all) - 1)];
    }

    for ($i = 0; $i < $length; $i++) {
      $j = random_int(0, $length - 1);
      [$passwordChars[$i], $passwordChars[$j]] = [$passwordChars[$j], $passwordChars[$i]];
    }

    return implode("", $passwordChars);
  }

  private function sendGeneratedPasswordEmail(User $user, string $passwordPlain): void
  {
    try {
      Mail::send(
        "auth::emails.credentials",
        [
          "user" => $user,
          "password" => $passwordPlain,
          "loginUrl" => rtrim(env("FRONTEND_URL", config("app.url")), "/") . "/auth/login",
        ],
        function ($message) use ($user) {
          $message->to($user->email)->subject("Akun Anda Telah Dibuat");
        },
      );
    } catch (\Throwable $e) {
    }
  }

  public function setUsername(User $user, string $username): array
  {
    $user->update(["username" => $username]);
    $user->refresh();

    $userArray = $user->toArray();
    $userArray["roles"] = $user->getRoleNames()->values();

    return ["user" => $userArray];
  }
}
