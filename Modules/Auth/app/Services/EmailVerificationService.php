<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\Services\EmailVerificationServiceInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Services\Support\VerificationTokenManager;
use Modules\Auth\Services\Support\VerificationValidator;

class EmailVerificationService implements EmailVerificationServiceInterface
{
    public const PURPOSE = VerificationTokenManager::PURPOSE_REGISTER;
    public const PURPOSE_CHANGE_EMAIL = VerificationTokenManager::PURPOSE_CHANGE_EMAIL;

    public function __construct(
        private readonly VerificationTokenManager $tokenManager,
        private readonly VerificationValidator $validator
    ) {}

    public function sendVerificationLink(User $user): ?string
    {
        return $this->tokenManager->sendVerificationLink($user);
    }

    public function verifyByCode(string $uuidOrToken, string $code): array
    {
        return $this->validator->verifyByCode($uuidOrToken, $code);
    }

    public function verifyByToken(string $token, string $uuid): array
    {
        return $this->validator->verifyByToken($token, $uuid);
    }

    public function sendChangeEmailLink(User $user, string $newEmail): ?string
    {
        return $this->tokenManager->sendChangeEmailLink($user, $newEmail);
    }

    public function verifyChangeByToken(string $token, string $uuid): array
    {
        return $this->validator->verifyChangeByToken($token, $uuid);
    }
}
