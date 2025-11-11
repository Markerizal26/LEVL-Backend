<?php

namespace Modules\Auth\Interfaces;

use Modules\Auth\Models\User;
use Modules\Auth\Support\TokenPairDTO;

interface AuthServiceInterface
{
    /** @return array<string,mixed> */
    public function register(array $validated, string $ip, ?string $userAgent): array;
    /** @return array<string,mixed> */
    public function login(string $login, string $password, string $ip, ?string $userAgent): array;
    /** @return array<string,mixed> */
    public function refresh(User $currentUser, string $refreshToken, string $ip, ?string $userAgent): array;
    public function logout(User $user, string $currentJwt, ?string $refreshToken = null): void;
    public function me(User $user): User;
}


