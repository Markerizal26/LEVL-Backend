<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Interfaces\AuthRepositoryInterface;
use Modules\Auth\Interfaces\AuthServiceInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Support\TokenPairDTO;
use Tymon\JWTAuth\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly JWTAuth $jwt,
        private readonly EmailVerificationService $emailVerification,
        private readonly LoginThrottlingService $throttle
    ) {}

    public function register(array $validated, string $ip, ?string $userAgent): array
    {
        $validated['password'] = Hash::make($validated['password']);
        $user = $this->authRepository->createUser($validated);

        $user->assignRole('student');

        $token = $this->jwt->fromUser($user);

        $deviceId = hash('sha256', ($ip ?? '') . ($userAgent ?? '') . $user->id);
        $refresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            deviceId: $deviceId
        );

        $pair = new TokenPairDTO(
            accessToken: $token,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $refresh->getAttribute('plain_token')
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

    public function login(string $login, string $password, string $ip, ?string $userAgent): array
    {
        $this->throttle->ensureNotLocked($login);
        if ($this->throttle->tooManyAttempts($login, $ip)) {
            $retryAfter = $this->throttle->getRetryAfterSeconds($login, $ip);
            $cfg = $this->throttle->getRateLimitConfig();
            $m = intdiv($retryAfter, 60);
            $s = $retryAfter % 60;
            $retryIn = $m > 0 ? ($m.' menit'.($s > 0 ? ' '.$s.' detik' : '')) : ($s.' detik');
            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan login. Maksimal {$cfg['max']} kali dalam {$cfg['decay']} menit. Coba lagi dalam {$retryIn}.",
            ]);
        }

        $user = $this->authRepository->findByLogin($login);
        if (! $user || ! Hash::check($password, $user->password)) {
            $this->throttle->hitAttempt($login, $ip);
            $this->throttle->recordFailureAndMaybeLock($login);
            throw ValidationException::withMessages([
                'login' => 'Username/email atau password salah.',
            ]);
        }

        $roles = $user->getRoleNames();
        $isPrivileged = $roles->contains(fn ($r) => in_array($r, ['superadmin', 'admin', 'instructor']));

        // Auto-verify privileged users (admin, superadmin, instructor) on first login
        $wasAutoVerified = false;
        if ($isPrivileged && ($user->status === 'pending' || $user->email_verified_at === null)) {
            $user->email_verified_at = now();
            $user->status = 'active';
            $user->save();
            $user->refresh(); // Refresh to get updated attributes
            $wasAutoVerified = true;
        }

        $token = $this->jwt->fromUser($user);

        $deviceId = hash('sha256', ($ip ?? '') . ($userAgent ?? '') . $user->id);
        $refresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            deviceId: $deviceId
        );

        $pair = new TokenPairDTO(
            accessToken: $token,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $refresh->getAttribute('plain_token')
        );

        $this->throttle->clearAttempts($login, $ip);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        $response = ['user' => $userArray] + $pair->toArray();



        if ($user->status === 'pending' && $user->email_verified_at === null && !$isPrivileged) {
            $verificationUuid = $this->emailVerification->sendVerificationLink($user);
            $response['status'] = 'pending';
            $response['message'] = 'Akun Anda belum aktif. Silakan verifikasi email terlebih dahulu.';
            if ($verificationUuid) {
                $response['verification_uuid'] = $verificationUuid;
            }
        } elseif ($user->status === 'inactive') {
            $response['status'] = 'inactive';
            $response['message'] = 'Akun Anda tidak aktif. Hubungi administrator.';
        } elseif ($user->status === 'banned') {
            $response['status'] = 'banned';
            $response['message'] = 'Akun Anda telah dibanned. Hubungi administrator.';
        } elseif ($wasAutoVerified) {
         
            $response['message'] = 'Login berhasil. Akun Anda telah otomatis diverifikasi.';
        }

        return $response;
    }

    public function refresh(User $currentUser, string $refreshToken, string $ip, ?string $userAgent): array
    {
        $record = $this->authRepository->findValidRefreshRecordByUser($refreshToken, $currentUser->id);
        if (! $record) {
            throw ValidationException::withMessages([
                'refresh_token' => 'Refresh token tidak valid atau kadaluarsa.',
            ]);
        }

        if ($record->isReplaced()) {
            $chain = $this->authRepository->findReplacedTokenChain($record->id);
            $deviceIds = collect($chain)->pluck('device_id')->unique()->filter()->toArray();
            
            foreach ($deviceIds as $deviceId) {
                $this->authRepository->revokeAllUserRefreshTokensByDevice($currentUser->id, $deviceId);
            }
            
            throw ValidationException::withMessages([
                'refresh_token' => 'Refresh token telah digunakan sebelumnya. Semua sesi perangkat telah dicabut karena potensi keamanan.',
            ]);
        }

        $deviceId = $record->device_id ?? hash('sha256', ($ip ?? '') . ($userAgent ?? '') . $currentUser->id);
        
        $newRefresh = $this->authRepository->createRefreshToken(
            userId: $currentUser->id,
            ip: $ip,
            userAgent: $userAgent,
            deviceId: $deviceId
        );

        $this->authRepository->markTokenAsReplaced($record->id, $newRefresh->id);

        $record->update([
            'last_used_at' => now(),
            'idle_expires_at' => now()->addDays(14),
        ]);

        $accessToken = $this->jwt->fromUser($currentUser);

        return [
            'access_token' => $accessToken,
            'expires_in' => $this->jwt->factory()->getTTL() * 60,
            'refresh_token' => $newRefresh->getAttribute('plain_token'),
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
        $passwordPlain = $this->generatePasswordFromNameEmail($validated['name'] ?? '', $validated['email'] ?? '');
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
        $passwordPlain = $this->generatePasswordFromNameEmail($validated['name'] ?? '', $validated['email'] ?? '');
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
        $passwordPlain = $this->generatePasswordFromNameEmail($validated['name'] ?? '', $validated['email'] ?? '');
        $validated['password'] = Hash::make($passwordPlain);
        $user = $this->authRepository->createUser($validated);
        $user->assignRole('Superadmin');

        $this->sendGeneratedPasswordEmail($user, $passwordPlain);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        return ['user' => $userArray];
    }

    private function generatePasswordFromNameEmail(string $name, string $email): string
    {
        $length = 14;
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnpqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '!@#$%^&*()-_=+[]{}';

        $passwordChars = [];
        $passwordChars[] = $upper[random_int(0, strlen($upper) - 1)];
        $passwordChars[] = $lower[random_int(0, strlen($lower) - 1)];
        $passwordChars[] = $numbers[random_int(0, strlen($numbers) - 1)];
        $passwordChars[] = $symbols[random_int(0, strlen($symbols) - 1)];

        $all = $upper.$lower.$numbers.$symbols;
        for ($i = count($passwordChars); $i < $length; $i++) {
            $passwordChars[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = 0; $i < $length; $i++) {
            $j = random_int(0, $length - 1);
            [$passwordChars[$i], $passwordChars[$j]] = [$passwordChars[$j], $passwordChars[$i]];
        }

        return implode('', $passwordChars);
    }

    private function sendGeneratedPasswordEmail(User $user, string $passwordPlain): void
    {
        try {
            Mail::send('auth::emails.credentials', [
                'user' => $user,
                'password' => $passwordPlain,
                'loginUrl' => rtrim(env('FRONTEND_URL', config('app.url')), '/').'/auth/login',
            ], function ($message) use ($user) {
                $message->to($user->email)->subject('Akun Anda Telah Dibuat');
            });
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
}
