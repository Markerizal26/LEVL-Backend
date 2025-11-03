<?php

namespace Modules\Auth\Interfaces;

use Modules\Auth\Models\JwtRefreshToken;
use Modules\Auth\Models\User;

interface AuthRepositoryInterface
{
    public function findActiveUserByLogin(string $login): ?User;
    public function createUser(array $data): User;
    public function createRefreshToken(int $userId, ?string $ip, ?string $userAgent, ?int $ttlMinutes = null): JwtRefreshToken;
    public function revokeRefreshToken(string $plainToken, int $userId): void;
    public function revokeAllUserRefreshTokens(int $userId): void;
    public function findValidRefreshRecordByUser(string $plainToken, int $userId): ?JwtRefreshToken;
}


