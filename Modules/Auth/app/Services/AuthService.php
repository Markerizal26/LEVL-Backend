<?php

namespace Modules\Auth\Services;

use App\Exceptions\BusinessException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\Contracts\Services\AuthServiceInterface;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Support\TokenPairDTO;
use Modules\Common\Models\Audit;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\CourseAdmin;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Tymon\JWTAuth\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly JWTAuth $jwt,
        private readonly EmailVerificationService $emailVerification,
        private readonly LoginThrottlingService $throttle,
    ) {}

    public function register(RegisterDTO|array $data, string $ip, ?string $userAgent): array
    {
        $validated = $data instanceof RegisterDTO ? $data->toArray() : $data;
        $validated['password'] = Hash::make($validated['password']);
        $user = $this->authRepository->createUser($validated);

        $user->assignRole('Student');

        $token = $this->jwt->fromUser($user);

        $deviceId = hash('sha256', ($ip ?? '').($userAgent ?? '').$user->id);
        $refresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            deviceId: $deviceId,
        );

        $pair = new TokenPairDTO(
            accessToken: $token,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $refresh->getAttribute('plain_token'),
        );

        $verificationUuid = $this->emailVerification->sendVerificationLink($user);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        $response = ['user' => $userArray] + $pair->toArray();

        if ($verificationUuid) {
            $response['verification_uuid'] = $verificationUuid;
        }

        return $response;
    }

    public function login(
        LoginDTO|string $loginOrDto,
        ?string $password,
        string $ip,
        ?string $userAgent,
    ): array {
        if ($loginOrDto instanceof LoginDTO) {
            $login = $loginOrDto->login;
            $password = $loginOrDto->password;
        } else {
            $login = $loginOrDto;
        }

        $this->throttle->ensureNotLocked($login);
        if ($this->throttle->tooManyAttempts($login, $ip)) {
            $retryAfter = $this->throttle->getRetryAfterSeconds($login, $ip);
            $cfg = $this->throttle->getRateLimitConfig();
            $m = intdiv($retryAfter, 60);
            $s = $retryAfter % 60;
            $retryIn = $m > 0 ? $m.' menit'.($s > 0 ? ' '.$s.' detik' : '') : $s.' detik';
            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan login. Maksimal {$cfg['max']} kali dalam {$cfg['decay']} menit. Coba lagi dalam {$retryIn}.",
            ]);
        }

        $user = $this->authRepository->findByLogin($login);
        if (! $user || ! Hash::check($password, $user->password)) {
            $this->throttle->hitAttempt($login, $ip);
            $this->throttle->recordFailureAndMaybeLock($login);
            throw new BusinessException(
                'Username/email atau password salah.',
                ['login' => ['Username/email atau password salah.']],
                401,
            );
        }

        $roles = $user->getRoleNames();
        $isPrivileged = $roles->intersect(['Superadmin', 'Admin', 'Instructor'])->isNotEmpty();

        $wasAutoVerified = false;
        if (
            $isPrivileged &&
            ($user->status === UserStatus::Pending || $user->email_verified_at === null)
        ) {
            $user->email_verified_at = now();
            $user->status = UserStatus::Active;
            $user->save();
            $user->refresh();
            $wasAutoVerified = true;
        }

        $token = $this->jwt->fromUser($user);

        $deviceId = hash('sha256', ($ip ?? '').($userAgent ?? '').$user->id);
        $refresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            deviceId: $deviceId,
        );

        $pair = new TokenPairDTO(
            accessToken: $token,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $refresh->getAttribute('plain_token'),
        );

        $this->throttle->clearAttempts($login, $ip);

        // Log successful login (NO sensitive data like password or tokens)
        activity('auth')
            ->causedBy($user)
            ->withProperties([
                'action' => 'login',
                'login_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username',
                'auto_verified' => $wasAutoVerified,
                'status' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
            ])
            ->log('Pengguna berhasil login');

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();
        $userArray['status'] =
          $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;

        $response = ['user' => $userArray] + $pair->toArray();

        if (
            $user->status === UserStatus::Pending &&
            $user->email_verified_at === null &&
            ! $isPrivileged
        ) {
            $verificationUuid = $this->emailVerification->sendVerificationLink($user);
            $response['status'] = UserStatus::Pending->value;
            $response['message'] = 'Akun Anda belum aktif. Silakan verifikasi email terlebih dahulu.';
            if ($verificationUuid) {
                $response['verification_uuid'] = $verificationUuid;
            }
        } elseif ($user->status === UserStatus::Inactive) {
            $response['status'] = UserStatus::Inactive->value;
            $response['message'] = 'Akun Anda tidak aktif. Hubungi administrator.';
        } elseif ($user->status === UserStatus::Banned) {
            $response['status'] = UserStatus::Banned->value;
            $response['message'] = 'Akun Anda telah dibanned. Hubungi administrator.';
        } elseif ($wasAutoVerified) {
            $response['message'] = 'Login berhasil. Akun Anda telah otomatis diverifikasi.';
        }

        return $response;
    }

    public function refresh(string $refreshToken, string $ip, ?string $userAgent): array
    {
        $record = $this->authRepository->findValidRefreshRecord($refreshToken);
        if (! $record) {
            throw ValidationException::withMessages([
                'refresh_token' => 'Refresh token tidak valid atau kadaluarsa.',
            ]);
        }

        $user = $record->user;
        if (! $user) {
            throw ValidationException::withMessages([
                'refresh_token' => 'User tidak ditemukan untuk refresh token ini.',
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'refresh_token' => 'Akun tidak aktif.',
            ]);
        }

        if ($record->isReplaced()) {
            $chain = $this->authRepository->findReplacedTokenChain($record->id);
            $deviceIds = collect($chain)->pluck('device_id')->unique()->filter()->toArray();

            foreach ($deviceIds as $deviceId) {
                $this->authRepository->revokeAllUserRefreshTokensByDevice($user->id, $deviceId);
            }

            throw ValidationException::withMessages([
                'refresh_token' => 'Refresh token telah digunakan sebelumnya. Semua sesi perangkat telah dicabut karena potensi keamanan.',
            ]);
        }

        $deviceId = $record->device_id ?? hash('sha256', ($ip ?? '').($userAgent ?? '').$user->id);

        $newRefresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            deviceId: $deviceId,
        );

        $this->authRepository->markTokenAsReplaced($record->id, $newRefresh->id);

        $record->update([
            'last_used_at' => now(),
            'idle_expires_at' => now()->addDays(14),
        ]);

        $accessToken = $this->jwt->fromUser($user);

        return [
            'access_token' => $accessToken,
            'expires_in' => $this->jwt->factory()->getTTL() * 60,
            'refresh_token' => $newRefresh->getAttribute('plain_token'),
        ];
    }

    public function logout(User $user, string $currentJwt, ?string $refreshToken = null): void
    {
        // Log logout (NO sensitive data like tokens)
        activity('auth')
            ->causedBy($user)
            ->withProperties([
                'action' => 'logout',
                'refresh_token_revoked' => $refreshToken !== null,
            ])
            ->log('Pengguna logout');

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
            $validated['name'] ?? '',
            $validated['email'] ?? '',
        );
        $validated['password'] = Hash::make($passwordPlain);
        $user = $this->authRepository->createUser($validated);
        $user->assignRole('Instructor');

        $this->sendGeneratedPasswordEmail($user, $passwordPlain);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        return ['user' => $userArray];
    }

    public function createAdmin(array $validated): array
    {
        $passwordPlain = $this->generatePasswordFromNameEmail(
            $validated['name'] ?? '',
            $validated['email'] ?? '',
        );
        $validated['password'] = Hash::make($passwordPlain);
        $user = $this->authRepository->createUser($validated);
        $user->assignRole('Admin');

        $this->sendGeneratedPasswordEmail($user, $passwordPlain);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        return ['user' => $userArray];
    }

    public function createSuperAdmin(array $validated): array
    {
        $passwordPlain = $this->generatePasswordFromNameEmail(
            $validated['name'] ?? '',
            $validated['email'] ?? '',
        );
        $validated['password'] = Hash::make($passwordPlain);
        $user = $this->authRepository->createUser($validated);
        $user->assignRole('Superadmin');

        $this->sendGeneratedPasswordEmail($user, $passwordPlain);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        return ['user' => $userArray];
    }

    public function listUsers(User $authUser, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $query = $this->buildUserListQuery();

        $isSuperadmin = $authUser->hasRole('Superadmin');
        $isAdmin = $authUser->hasRole('Admin');

        if ($isAdmin && ! $isSuperadmin) {
            $this->applyAdminUserScope($query->getEloquentBuilder(), $authUser);
        }

        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform(fn ($u) => $this->transformUserForList($u));

        return $paginator;
    }

    public function showUser(User $authUser, User $target): array
    {
        $isSuperadmin = $authUser->hasRole('Superadmin');
        $isAdmin = $authUser->hasRole('Admin');

        if (! $isSuperadmin && ! $isAdmin) {
            throw new AuthorizationException(__('messages.unauthorized'));
        }

        if ($isAdmin && ! $isSuperadmin && ! $this->adminCanSeeUser($authUser, $target)) {
            throw new AuthorizationException(__('messages.auth.no_access_to_user'));
        }

        return $this->formatUserDetails($target);
    }

    public function updateUserStatus(User $user, string $status): User
    {
        if ($status === UserStatus::Pending->value) {
            throw ValidationException::withMessages([
                'status' => 'Mengubah status ke pending tidak diperbolehkan.',
            ]);
        }

        $user->status = UserStatus::from($status);
        $user->save();

        return $user->fresh();
    }

    private function buildUserListQuery(): QueryBuilder
    {
        $searchQuery = request('filter.search');

        $builder = QueryBuilder::for(User::class)
            ->select(['id', 'name', 'email', 'username', 'status', 'created_at', 'email_verified_at'])
            ->with(['roles', 'media']);

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = User::search($searchQuery)->keys()->toArray();
            $builder->whereIn('id', $ids);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('role', function (Builder $query, $value) {
                    $roles = is_array($value)
                      ? $value
                      : Str::of($value)->explode(',')->map(fn ($r) => trim($r))->toArray();
                    $query->whereHas('roles', fn ($q) => $q->whereIn('name', $roles));
                }),
                AllowedFilter::callback('created_from', function (Builder $query, $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_to', function (Builder $query, $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
            ])
            ->allowedSorts(['name', 'email', 'username', 'status', 'created_at'])
            ->defaultSort('-created_at');
    }

    private function transformUserForList(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'avatar' => $user->avatar_url,
            'status' => $user->status,
            'created_at' => $user->created_at?->toISOString(),
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'roles' => method_exists($user, 'getRoleNames')
              ? $user->getRoleNames()->values()
              : $user->roles?->pluck('name')->values() ?? [],
        ];
    }

    private function formatUserDetails(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'avatar' => $user->avatar_url,
            'status' => $user->status,
            'created_at' => $user->created_at?->toISOString(),
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'roles' => method_exists($user, 'getRoleNames')
              ? $user->getRoleNames()->values()
              : $user->roles?->pluck('name')->values() ?? [],
        ];
    }

    private function applyAdminUserScope(Builder $query, User $admin): void
    {
        $managedCourseIds = $this->managedCourseIds($admin);

        $query->where(function (Builder $visibility) use ($managedCourseIds) {
            $visibility
                ->where(function (Builder $studentScope) use ($managedCourseIds) {
                    if ($managedCourseIds->isEmpty()) {
                        $studentScope->whereRaw('0 = 1');

                        return;
                    }

                    $studentScope
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->where('name', 'Student');
                        })
                        ->whereHas('enrollments', function ($enrollmentQuery) use ($managedCourseIds) {
                            $enrollmentQuery->whereIn('course_id', $managedCourseIds);
                        });
                })
                ->orWhere(function (Builder $adminScope) {
                    $adminScope
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->where('name', 'Admin');
                        })
                        ->whereDoesntHave('roles', function ($roleQuery) {
                            $roleQuery->where('name', 'Superadmin');
                        });
                });
        });
    }

    private function adminCanSeeUser(User $admin, User $target): bool
    {
        if ($target->hasRole('Superadmin')) {
            return false;
        }

        if ($target->hasRole('Admin')) {
            return true;
        }

        if (! $target->hasRole('Student')) {
            return false;
        }

        $managedCourseIds = $this->managedCourseIds($admin);
        if ($managedCourseIds->isEmpty()) {
            return false;
        }

        return Enrollment::query()
            ->where('user_id', $target->id)
            ->whereIn('course_id', $managedCourseIds)
            ->exists();
    }

    private function managedCourseIds(User $admin): Collection
    {
        return CourseAdmin::query()
            ->where('user_id', $admin->id)
            ->pluck('course_id')
            ->unique()
            ->values();
    }

    private function generatePasswordFromNameEmail(string $name, string $email): string
    {
        return Str::password(14, symbols: true);
    }

    private function sendGeneratedPasswordEmail(User $user, string $passwordPlain): void
    {
        try {
            Mail::send(
                'auth::emails.credentials',
                [
                    'user' => $user,
                    'password' => $passwordPlain,
                    'loginUrl' => rtrim(config('app.frontend_url'), '/').'/auth/login',
                ],
                function ($message) use ($user) {
                    $message->to($user->email)->subject('Akun Anda Telah Dibuat');
                },
            );
        } catch (\Throwable $e) {
        }
    }

    public function setUsername(User $user, string $username): array
    {
        $user->update(['username' => $username]);
        $user->refresh();

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        return ['user' => $userArray];
    }

    public function logProfileUpdate(
        User $user,
        array $changes,
        ?string $ip,
        ?string $userAgent,
    ): void {
        Audit::create([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'meta' => ['action' => 'profile.update', 'changes' => $changes],
            'logged_at' => now(),
        ]);
    }

    public function logEmailChangeRequest(
        User $user,
        string $newEmail,
        string $uuid,
        ?string $ip,
        ?string $userAgent,
    ): void {
        Audit::create([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'meta' => [
                'action' => 'email.change.request',
                'new_email' => $newEmail,
                'uuid' => $uuid,
            ],
            'logged_at' => now(),
        ]);
    }
}
