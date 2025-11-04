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

        $refresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            ttlMinutes: (int) config('jwt.refresh_ttl')
        );

        $pair = new TokenPairDTO(
            accessToken: $token,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $refresh->getAttribute('plain_token')
        );

        $this->emailVerification->sendVerificationLink($user);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        return ['user' => $userArray] + $pair->toArray();
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

        $user = $this->authRepository->findActiveUserByLogin($login);
        if (! $user || ! Hash::check($password, $user->password)) {
            $this->throttle->hitAttempt($login, $ip);
            $this->throttle->recordFailureAndMaybeLock($login);
            throw ValidationException::withMessages([
                'login' => 'Kredensial tidak valid.',
            ]);
        }

        if ($user->email_verified_at === null || $user->status !== 'active') {
            $user->email_verified_at = now();
            $user->status = 'active';
            $user->save();
        }

        $token = $this->jwt->fromUser($user);

        $refresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            ttlMinutes: (int) config('jwt.refresh_ttl')
        );

        $pair = new TokenPairDTO(
            accessToken: $token,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $refresh->getAttribute('plain_token')
        );

        $this->throttle->clearAttempts($login, $ip);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        return ['user' => $userArray] + $pair->toArray();
    }

    public function refresh(User $currentUser, string $refreshToken): array
    {
        $record = $this->authRepository->findValidRefreshRecordByUser($refreshToken, $currentUser->id);
        if (! $record) {
            throw ValidationException::withMessages([
                'refresh_token' => 'Refresh token tidak valid atau kadaluarsa.',
            ]);
        }

        $accessToken = $this->jwt->fromUser($currentUser);

        return [
            'access_token' => $accessToken,
            'expires_in' => $this->jwt->factory()->getTTL() * 60,
        ];
    }

    public function logout(User $user, string $currentJwt, ?string $refreshToken = null): void
    {
        $this->jwt->invalidate($currentJwt);
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
        $user->assignRole('instructor');

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
        $user->assignRole('admin');

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
        $user->assignRole('super-admin');

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
                'loginUrl' => config('app.url').'/login',
            ], function ($message) use ($user) {
                $message->to($user->email)->subject('Akun Anda Telah Dibuat');
            });
        } catch (\Throwable $e) {
            // no-op: avoid breaking flow if mail fails
        }
    }
}
