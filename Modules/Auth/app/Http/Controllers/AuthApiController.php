<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\AbstractProvider as SocialiteAbstractProvider;
use Modules\Auth\Http\Requests\CreateManagedUserRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\LogoutRequest;
use Modules\Auth\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\RequestEmailChangeRequest;
use Modules\Auth\Http\Requests\ResendCredentialsRequest;
use Modules\Auth\Http\Requests\SetUsernameRequest;
use Modules\Auth\Http\Requests\UpdateProfileRequest;
use Modules\Auth\Http\Requests\UpdateUserStatusRequest;
use Modules\Auth\Http\Requests\VerifyEmailByTokenRequest;
use Modules\Auth\Http\Requests\VerifyEmailChangeRequest;
use Modules\Auth\Http\Requests\VerifyEmailRequest;
use Modules\Auth\Interfaces\AuthRepositoryInterface;
use Modules\Auth\Models\SocialAccount;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\EmailVerificationService;
use Modules\Common\Models\Audit;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\CourseAdmin;
use Tymon\JWTAuth\JWTAuth;

class AuthApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth, private readonly EmailVerificationService $emailVerification) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $this->auth->register(
            validated: $request->validated(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->created($data, 'Registrasi berhasil. Silakan periksa email Anda untuk verifikasi.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->string('login');
        $data = $this->auth->login(
            login: $login,
            password: $request->input('password'),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );


        if (isset($data['message'])) {
            return $this->success($data, $data['message']);
        }

        return $this->success($data, 'Login berhasil.');
    }

    public function createInstructor(CreateManagedUserRequest $request): JsonResponse
    {
        $data = $this->auth->createInstructor($request->validated());

        return $this->created($data, 'Instructor berhasil dibuat.');
    }

    public function createAdmin(CreateManagedUserRequest $request): JsonResponse
    {
        $data = $this->auth->createAdmin($request->validated());

        return $this->created($data, 'Admin berhasil dibuat.');
    }

    public function createSuperAdmin(CreateManagedUserRequest $request): JsonResponse
    {
        $data = $this->auth->createSuperAdmin($request->validated());

        return $this->created($data, 'Super admin berhasil dibuat.');
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            /** @var \Modules\Auth\Models\User $authUser */
            $authUser = auth('api')->user();
            $data = $this->auth->refresh(
                $authUser,
                $request->string('refresh_token'),
                $request->ip(),
                $request->userAgent()
            );
        } catch (ValidationException $e) {
            return $this->error('Refresh token tidak valid atau tidak cocok dengan akun saat ini.', 401);
        }

        return $this->success($data, 'Token akses berhasil diperbarui.');
    }

    public function logout(LogoutRequest $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $currentJwt = $request->bearerToken();
        if (! $currentJwt) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $this->auth->logout($user, $currentJwt, $request->input('refresh_token'));

        return $this->success([], 'Logout berhasil.');
    }

    public function profile(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        return $this->success($user->toArray(), 'Profil berhasil diambil.');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $validated = $request->validated();

        $changes = [];
        if ($user->name !== $validated['name']) {
            $changes['name'] = [$user->name, $validated['name']];
            $user->name = $validated['name'];
        }
        if ($user->username !== $validated['username']) {
            $changes['username'] = [$user->username, $validated['username']];
            $user->username = $validated['username'];
        }

        if ($request->hasFile('avatar')) {
            $old = $user->avatar_path;
            $file = $request->file('avatar');
            $path = app(\App\Services\UploadService::class)->storePublic($file, 'avatars');
            $user->avatar_path = $path;
            $changes['avatar_path'] = [$old, $path];
            if ($old) {
                app(\App\Services\UploadService::class)->deletePublic($old);
            }
        }

        $user->save();

        Audit::create([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => ['action' => 'profile.update', 'changes' => $changes],
            'logged_at' => now(),
        ]);

        return $this->success($user->toArray(), 'Profil berhasil diperbarui.');
    }

    public function googleRedirect(Request $request)
    {
        try {
            /** @var SocialiteFactory $socialite */
            $socialite = app(SocialiteFactory::class);
            $provider = $socialite->driver('google');
            /** @var SocialiteAbstractProvider $provider */
            $provider = $provider->stateless();
            $redirectResponse = $provider->redirect();

            return $redirectResponse;
        } catch (\Throwable $e) {
            return $this->error('Tidak dapat menginisiasi Google OAuth. Silakan login manual.', 400);
        }
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $errorUrl = $frontendUrl.'/auth/login?error=google_login_failed';

        try {
            /** @var SocialiteFactory $socialite */
            $socialite = app(SocialiteFactory::class);
            $provider = $socialite->driver('google');
            /** @var SocialiteAbstractProvider $provider */
            $provider = $provider->stateless();
            $googleUser = $provider->user();
        } catch (\Throwable $e) {
            return redirect($errorUrl);
        }

        $email = $googleUser->getEmail();
        $name = $googleUser->getName() ?: ($googleUser->user['given_name'] ?? 'Google User');
        $providerId = $googleUser->getId();
        $provider = 'google';

        // Find existing user by email or create a new one
        $user = User::query()->where('email', $email)->first();
        $isNewUser = ! $user;
        if ($isNewUser) {
            $user = User::query()->create([
                'name' => $name,
                'username' => null,
                'email' => $email,
                // random password; not used for social login
                'password' => \Illuminate\Support\Str::random(32),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }


        $account = SocialAccount::query()->firstOrNew([
            'provider_name' => $provider,
            'provider_id' => $providerId,
        ]);
        $account->user_id = $user->id;
        $account->token = $googleUser->token ?? null;
        $account->refresh_token = $googleUser->refreshToken ?? null;
        $account->save();

        /** @var JWTAuth $jwt */
        $jwt = app(JWTAuth::class);
        $accessToken = $jwt->fromUser($user);

        /** @var AuthRepositoryInterface $authRepo */
        $authRepo = app(AuthRepositoryInterface::class);
        $deviceId = hash('sha256', ($request->ip() ?? '') . ($request->userAgent() ?? '') . $user->id);
        $refresh = $authRepo->createRefreshToken(
            userId: $user->id,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            deviceId: $deviceId
        );

        // Redirect to frontend with tokens in hash fragment (more secure, not sent to server)
        $successUrl = $frontendUrl.'/auth/callback?'
            .http_build_query([
                'access_token' => $accessToken,
                'refresh_token' => $refresh->getAttribute('plain_token'),
                'expires_in' => $jwt->factory()->getTTL() * 60,
                'provider' => $provider,
                'needs_username' => $isNewUser && ! $user->username ? '1' : '0',
            ]);

        return redirect($successUrl);
    }

    public function sendEmailVerification(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        if ($user->email_verified_at && $user->status === 'active') {
            return $this->success([], 'Email Anda sudah terverifikasi.');
        }

        $uuid = $this->emailVerification->sendVerificationLink($user);
        if ($uuid === null) {
            return $this->success([], 'Email Anda sudah terverifikasi.');
        }

        return $this->success(['uuid' => $uuid], 'Tautan verifikasi telah dikirim ke email Anda. Berlaku 3 menit dan hanya bisa digunakan sekali.');
    }

    public function requestEmailChange(RequestEmailChangeRequest $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $validated = $request->validated();

        $uuid = $this->emailVerification->sendChangeEmailLink($user, $validated['new_email']);

        Audit::create([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => ['action' => 'email.change.request', 'new_email' => $validated['new_email'], 'uuid' => $uuid],
            'logged_at' => now(),
        ]);

        return $this->success(['uuid' => $uuid], 'Tautan verifikasi perubahan email telah dikirim. Berlaku 3 menit.');
    }

    public function verifyEmailChange(VerifyEmailChangeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->emailVerification->verifyChangeByCode($validated['uuid'], $validated['code']);

        if ($result['status'] === 'ok') {
            return $this->success([], 'Email berhasil diubah dan terverifikasi.');
        }
        if ($result['status'] === 'expired') {
            return $this->error('Kode verifikasi telah kedaluwarsa.', 422);
        }
        if ($result['status'] === 'invalid') {
            return $this->error('Kode verifikasi salah.', 422);
        }
        if ($result['status'] === 'email_taken') {
            return $this->error('Email sudah digunakan oleh akun lain.', 422);
        }
        if ($result['status'] === 'not_found') {
            return $this->error('Tautan verifikasi tidak ditemukan.', 404);
        }

        return $this->error('Verifikasi perubahan email gagal.', 422);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $request->validated();

        $uuidOrToken = $request->input('token') ?? $request->input('uuid');
        $code = $request->string('code');

        $result = $this->emailVerification->verifyByCode($uuidOrToken, $code);

        if ($result['status'] === 'ok') {
            return $this->success([], 'Email Anda berhasil diverifikasi.');
        }

        if ($result['status'] === 'expired') {
            return $this->error('Kode verifikasi telah kedaluwarsa.', 422);
        }

        if ($result['status'] === 'invalid') {
            return $this->error('Kode verifikasi salah atau token tidak valid.', 422);
        }

        if ($result['status'] === 'not_found') {
            return $this->error('Tautan verifikasi tidak ditemukan.', 404);
        }

        return $this->error('Verifikasi gagal.', 422);
    }

    public function verifyEmailByToken(VerifyEmailByTokenRequest $request): JsonResponse
    {
        $request->validated();
        $token = $request->string('token');

        $result = $this->emailVerification->verifyByToken($token);

        if ($result['status'] === 'ok') {
            return $this->success([], 'Email Anda berhasil diverifikasi.');
        }

        if ($result['status'] === 'expired') {
            return $this->error('Link verifikasi telah kedaluwarsa.', 422);
        }

        if ($result['status'] === 'invalid') {
            return $this->error('Link verifikasi tidak valid atau sudah digunakan.', 422);
        }

        if ($result['status'] === 'not_found') {
            return $this->error('Link verifikasi tidak ditemukan.', 404);
        }

        return $this->error('Verifikasi gagal.', 422);
    }

    public function resendCredentials(ResendCredentialsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $target = User::query()->find($validated['user_id']);
        if (! $target) {
            return $this->error('User tidak ditemukan', 404);
        }

        $isAllowedRole = $target->hasRole('Admin') || $target->hasRole('Superadmin') || $target->hasRole('Instructor');
        $isPending = ($target->status ?? null) === 'pending';
        if (! ($isAllowedRole && $isPending)) {
            return $this->error('Hanya untuk akun Admin, Superadmin, atau Instructor yang berstatus pending.', 422);
        }

        $passwordPlain = (new \ReflectionClass($this->auth))->getMethod('generatePasswordFromNameEmail')->invoke($this->auth, $target->name, $target->email);
        $target->password = \Illuminate\Support\Facades\Hash::make($passwordPlain);
        $target->save();

        (new \ReflectionClass($this->auth))->getMethod('sendGeneratedPasswordEmail')->invoke($this->auth, $target, $passwordPlain);

        return $this->success(['user' => $target->toArray()], 'Kredensial berhasil dikirim ulang.');
    }

    public function updateUserStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $newStatus = $request->string('status');
        if ($newStatus === 'pending') {
            return $this->error('Mengubah status ke pending tidak diperbolehkan.', 422);
        }

        $user->status = $newStatus;
        $user->save();

        return $this->success(['user' => $user->toArray()], 'Status pengguna berhasil diperbarui.');
    }

    public function listUsers(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $authUser */
        $authUser = auth('api')->user();
        if (! $authUser) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $isSuperadmin = $authUser->hasRole('Superadmin');
        $isAdmin = $authUser->hasRole('Admin');
        if (! $isSuperadmin && ! $isAdmin) {
            return $this->error('Tidak terotorisasi.', 403);
        }

        $perPage = max(1, (int) ($request->query('per_page', 15)));
        $q = trim((string) $request->query('search', ''));
        $status = $request->input('filter.status');
        $role = $request->input('filter.role');
        $createdFrom = $request->input('filter.created_from');
        $createdTo = $request->input('filter.created_to');
        $sort = (string) $request->query('sort', '-created_at');

        $query = User::query()->select(['id', 'name', 'email', 'username', 'avatar_path', 'status', 'created_at', 'email_verified_at'])->with('roles');
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('username', 'like', '%'.$q.'%');
            });
        }
        if (is_string($status) && $status !== '') {
            $statuses = array_values(array_filter(array_map('trim', explode(',', $status))));
            if (! empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }
        if (is_string($role) && $role !== '') {
            $roles = array_values(array_filter(array_map('trim', explode(',', $role))));
            if (! empty($roles)) {
                $query->whereHas('roles', function ($q2) use ($roles) {
                    $q2->whereIn('name', $roles);
                });
            }
        }
        if (is_string($createdFrom) && $createdFrom !== '') {
            $query->whereDate('created_at', '>=', $createdFrom);
        }
        if (is_string($createdTo) && $createdTo !== '') {
            $query->whereDate('created_at', '<=', $createdTo);
        }

        if ($isAdmin && ! $isSuperadmin) {
            $this->applyAdminUserScope($query, $authUser);
        }

        $direction = 'asc';
        $field = $sort;
        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $field = substr($sort, 1);
        }
        $allowedSorts = ['name', 'email', 'username', 'status', 'created_at'];
        if (! in_array($field, $allowedSorts, true)) {
            $field = 'created_at';
            $direction = 'desc';
        }
        $query->orderBy($field, $direction);

        $paginator = $query->paginate($perPage)->appends($request->query());
        $paginator->getCollection()->transform(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'username' => $u->username,
                'avatar' => $u->avatar_path ? asset('storage/'.$u->avatar_path) : null,
                'status' => $u->status,
                'created_at' => $u->created_at?->toISOString(),
                'email_verified_at' => $u->email_verified_at?->toISOString(),
                'roles' => method_exists($u, 'getRoleNames') ? $u->getRoleNames()->values() : ($u->roles?->pluck('name')->values() ?? []),
            ];
        });

        return $this->paginateResponse($paginator);
    }

    public function showUser(User $user): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $authUser */
        $authUser = auth('api')->user();
        if (! $authUser) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $isSuperadmin = $authUser->hasRole('Superadmin');
        $isAdmin = $authUser->hasRole('Admin');
        if (! $isSuperadmin && ! $isAdmin) {
            return $this->error('Tidak terotorisasi.', 403);
        }

        if ($isAdmin && ! $isSuperadmin && ! $this->adminCanSeeUser($authUser, $user)) {
            return $this->error('Anda tidak memiliki akses untuk melihat pengguna ini.', 403);
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'avatar' => $user->avatar_path ? asset('storage/'.$user->avatar_path) : null,
            'status' => $user->status,
            'created_at' => $user->created_at?->toISOString(),
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values() : ($user->roles?->pluck('name')->values() ?? []),
        ];

        return $this->success(['user' => $data]);
    }

    public function setUsername(SetUsernameRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        if ($user->username) {
            return $this->error('Username sudah diatur untuk akun Anda.', 422);
        }

        $data = $this->auth->setUsername($user, $request->validated('username'));

        return $this->success($data, 'Username berhasil diatur.');
    }

    /**
     * Limit user visibility for Admin role.
     */
    private function applyAdminUserScope(Builder $query, User $admin): void
    {
        $managedCourseIds = $this->managedCourseIds($admin);

        $query->where(function (Builder $visibility) use ($managedCourseIds) {
            $visibility->where(function (Builder $studentScope) use ($managedCourseIds) {
                if ($managedCourseIds->isEmpty()) {
                    $studentScope->whereRaw('0 = 1');

                    return;
                }

                $studentScope->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Student');
                })->whereHas('enrollments', function ($enrollmentQuery) use ($managedCourseIds) {
                    $enrollmentQuery->whereIn('course_id', $managedCourseIds);
                });
            })->orWhere(function (Builder $adminScope) {
                $adminScope->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Admin');
                })->whereDoesntHave('roles', function ($roleQuery) {
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
}
