<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\EmailVerificationService;
use App\Support\ApiResponse;

class AuthApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth, private readonly EmailVerificationService $emailVerification)
    {
        
    }

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

        try {
            $data = $this->auth->login(
                login: $login,
                password: $request->input('password'),
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal', 422, $e->errors());
        }

        return $this->success($data, 'Login berhasil.');
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        try {
            /** @var \Modules\Auth\Models\User $authUser */
            $authUser = auth('api')->user();
            $data = $this->auth->refresh($authUser, $request->string('refresh_token'));
        } catch (ValidationException $e) {
            return $this->error('Refresh token tidak valid atau tidak cocok dengan akun saat ini.', 401);
        }

        return $this->success($data, 'Token akses berhasil diperbarui.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['nullable', 'string'],
        ]);

        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $currentJwt = $request->bearerToken();
        if (!$currentJwt) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $this->auth->logout($user, $currentJwt, $request->input('refresh_token'));

        return $this->success([], 'Logout berhasil.');
    }

    public function profile(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        return $this->success($user->toArray(), 'Profil berhasil diambil.');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,'.$user->id],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'avatar.image' => 'Avatar harus berupa gambar.',
            'avatar.mimes' => 'Avatar harus berformat jpg, jpeg, png, atau webp.',
            'avatar.max' => 'Ukuran avatar maksimal 2MB.',
        ]);

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

        \Modules\Operations\Models\SystemAudit::create([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => [ 'action' => 'profile.update', 'changes' => $changes ],
            'logged_at' => now(),
        ]);

        return $this->success($user->toArray(), 'Profil berhasil diperbarui.');
    }

    public function sendEmailVerification(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
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

    public function requestEmailChange(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $validated = $request->validate([
            'new_email' => ['required', 'email:rfc', 'max:191', 'unique:users,email,'.$user->id],
        ], [
            'new_email.required' => 'Email baru wajib diisi.',
            'new_email.email' => 'Format email tidak valid.',
            'new_email.unique' => 'Email tersebut sudah digunakan.',
        ]);

        $uuid = $this->emailVerification->sendChangeEmailLink($user, $validated['new_email']);

        \Modules\Operations\Models\SystemAudit::create([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => [ 'action' => 'email.change.request', 'new_email' => $validated['new_email'], 'uuid' => $uuid ],
            'logged_at' => now(),
        ]);

        return $this->success(['uuid' => $uuid], 'Tautan verifikasi perubahan email telah dikirim. Berlaku 3 menit.');
    }

    public function verifyEmailChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'string'],
            'code' => ['required', 'string'],
        ], [
            'uuid.required' => 'UUID wajib diisi.',
            'code.required' => 'Kode wajib diisi.',
        ]);

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

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'uuid' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $result = $this->emailVerification->verifyByCode($request->string('uuid'), $request->string('code'));

        if ($result['status'] === 'ok') {
            return $this->success([], 'Email Anda berhasil diverifikasi.');
        }

        if ($result['status'] === 'expired') {
            return $this->error('Kode verifikasi telah kedaluwarsa.', 422);
        }

        if ($result['status'] === 'invalid') {
            return $this->error('Kode verifikasi salah.', 422);
        }

        if ($result['status'] === 'not_found') {
            return $this->error('Tautan verifikasi tidak ditemukan.', 404);
        }

        return $this->error('Verifikasi gagal.', 422);
    }
}


