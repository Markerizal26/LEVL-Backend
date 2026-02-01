<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\Services\AuthServiceInterface;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\Models\User;
use Modules\Auth\Services\Support\AuthCredentialProcessor;
use Modules\Auth\Services\Support\AuthRegistrationProcessor;
use Modules\Auth\Services\Support\AuthSessionProcessor;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRegistrationProcessor $registrationProcessor,
        private readonly AuthSessionProcessor $sessionProcessor,
        private readonly AuthCredentialProcessor $credentialProcessor,
        private readonly EmailVerificationService $emailVerification
    ) {}

    public function register(RegisterDTO|array $data, string $ip, ?string $userAgent): array
    {
        return $this->registrationProcessor->register($data, $ip, $userAgent);
    }

    public function login(
        LoginDTO|string $loginOrDto,
        ?string $password,
        string $ip,
        ?string $userAgent,
    ): array {
        return $this->sessionProcessor->login($loginOrDto, $password, $ip, $userAgent);
    }

    public function refresh(string $refreshToken, string $ip, ?string $userAgent): array
    {
        return $this->sessionProcessor->refresh($refreshToken, $ip, $userAgent);
    }

    public function logout(User $user, string $currentJwt, ?string $refreshToken = null): void
    {
        $this->sessionProcessor->logout($user, $currentJwt, $refreshToken);
    }

    public function me(User $user): User
    {
        return $user;
    }

    public function verifyEmail(string $token, string $uuid): array
    {
        return $this->emailVerification->verifyByToken($token, $uuid);
    }

    public function sendEmailVerificationLink(User $user): ?string
    {
        return $this->emailVerification->sendVerificationLink($user);
    }

    public function setUsername(User $user, string $username): array
    {
        return $this->credentialProcessor->setUsername($user, $username);
    }

    public function setPassword(User $user, string $password): array
    {
        return $this->credentialProcessor->setPassword($user, $password);
    }

    public function logProfileUpdate(
        User $user,
        array $changes,
        ?string $ip,
        ?string $userAgent,
    ): void {
        $this->credentialProcessor->logProfileUpdate($user, $changes, $ip, $userAgent);
    }

    public function logEmailChangeRequest(
        User $user,
        string $newEmail,
        string $uuid,
        ?string $ip,
        ?string $userAgent,
    ): void {
        $this->credentialProcessor->logEmailChangeRequest($user, $newEmail, $uuid, $ip, $userAgent);
    }

    public function createUserFromGoogle($googleUser): User
    {
        return $this->registrationProcessor->createUserFromGoogle($googleUser);
    }

    public function generateDevTokens(string $ip, ?string $userAgent): array
    {
        return $this->registrationProcessor->generateDevTokens($ip, $userAgent);
    }
}
