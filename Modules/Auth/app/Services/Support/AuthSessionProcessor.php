<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Support;

use App\Exceptions\BusinessException;
use App\Jobs\LogActivityJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Services\EmailVerificationService;
use Modules\Auth\Services\LoginThrottlingService;
use Modules\Auth\Support\TokenPairDTO;
use Tymon\JWTAuth\JWTAuth;

class AuthSessionProcessor
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly JWTAuth $jwt,
        private readonly EmailVerificationService $emailVerification,
        private readonly LoginThrottlingService $throttle
    ) {}

    public function login(
        LoginDTO|string $loginOrDto,
        ?string $password,
        string $ip,
        ?string $userAgent
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
                'login' => __('messages.auth.throttle_message', ['max' => $cfg['max'], 'decay' => $cfg['decay'], 'retryIn' => $retryIn]),
            ]);
        }

        $user = $this->authRepository->findByLogin($login);
        if (! $user || ! Hash::check($password, $user->password)) {
            $this->throttle->hitAttempt($login, $ip);
            $this->throttle->recordFailureAndMaybeLock($login);
            throw new BusinessException(
                __('messages.auth.invalid_credentials'),
                ['login' => [__('messages.auth.invalid_credentials')]],
                401
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

        dispatch(new LogActivityJob([
            'log_name' => 'auth',
            'causer_id' => $user->id,
            'properties' => [
                'action' => 'login',
                'login_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username',
                'auto_verified' => $wasAutoVerified,
                'status' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
            ],
            'description' => __('messages.auth.log_user_login'),
        ]));

        $userArray = $user->toArray();
        $userArray['roles'] = $roles->values();
        $userArray['avatar_url'] = $user->avatar_url;
        $userArray['status'] = $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;

        $response = ['user' => $userArray] + $pair->toArray();

        if (
            $user->status === UserStatus::Pending &&
            $user->email_verified_at === null &&
            ! $isPrivileged
        ) {
            $verificationUuid = $this->emailVerification->sendVerificationLink($user);
            $response['status'] = UserStatus::Pending->value;
            $response['message'] = __('messages.auth.email_not_verified');
            if ($verificationUuid) {
                $response['verification_uuid'] = $verificationUuid;
            }
        } elseif ($user->status === UserStatus::Inactive) {
            $response['status'] = UserStatus::Inactive->value;
            $response['message'] = __('messages.auth.account_not_active_contact_admin');
        } elseif ($user->status === UserStatus::Banned) {
            $response['status'] = UserStatus::Banned->value;
            $response['message'] = __('messages.auth.account_banned_contact_admin');
        } elseif ($wasAutoVerified) {
            $response['message'] = __('messages.auth.login_success_auto_verified');
        }

        return $response;
    }

    public function refresh(string $refreshToken, string $ip, ?string $userAgent): array
    {
        $record = $this->authRepository->findValidRefreshRecord($refreshToken);
        if (! $record) {
            throw ValidationException::withMessages([
                'refresh_token' => __('messages.auth.refresh_token_invalid'),
            ]);
        }

        $user = $record->user;
        if (! $user) {
            throw ValidationException::withMessages([
                'refresh_token' => __('messages.auth.refresh_token_user_not_found'),
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'refresh_token' => __('messages.auth.account_not_active'),
            ]);
        }

        if ($record->isReplaced()) {
            $chain = $this->authRepository->findReplacedTokenChain($record->id);
            $deviceIds = collect($chain)->pluck('device_id')->unique()->filter()->toArray();

            foreach ($deviceIds as $deviceId) {
                $this->authRepository->revokeAllUserRefreshTokensByDevice($user->id, $deviceId);
            }

            throw ValidationException::withMessages([
                'refresh_token' => __('messages.auth.refresh_token_compromised'),
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
        dispatch(new LogActivityJob([
            'log_name' => 'auth',
            'causer_id' => $user->id,
            'properties' => [
                'action' => 'logout',
                'refresh_token_revoked' => $refreshToken !== null,
            ],
            'description' => __('messages.auth.log_user_logout'),
        ]));

        $this->jwt->setToken($currentJwt)->invalidate();
        if ($refreshToken) {
            $this->authRepository->revokeRefreshToken($refreshToken, $user->id);
        }
    }
}
