<?php

namespace Modules\Auth\Contracts\Services;

use Modules\Auth\Models\User;

interface EmailVerificationServiceInterface
{
    public function sendVerificationLink(User $user): ?string;

    public function verifyByCode(string $uuidOrToken, string $code): array;

    public function verifyByToken(string $token): array;

    public function sendChangeEmailLink(User $user, string $newEmail): ?string;

    public function verifyChangeByCode(string $uuid, string $code): array;
}
