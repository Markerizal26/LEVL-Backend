<?php

namespace Modules\Auth\Interfaces;

use Modules\Auth\Models\JwtRefreshToken;
use Modules\Auth\Models\User;

interface AuthRepositoryInterface
{
    public function findActiveUserByLogin(string $login): ?User;

    public function findByLogin(string $login): ?User;

    public function createUser(array $data): User;

    public function createRefreshToken(int $userId, ?string $ip, ?string $userAgent, ?string $deviceId = null, ?int $idleTtlDays = null, ?int $absoluteTtlDays = null): JwtRefreshToken;

    public function revokeRefreshToken(string $plainToken, int $userId): void;

    public function revokeAllUserRefreshTokens(int $userId): void;

    public function revokeAllUserRefreshTokensByDevice(int $userId, string $deviceId): void;

    public function findValidRefreshRecordByUser(string $plainToken, int $userId): ?JwtRefreshToken;

    public function markTokenAsReplaced(int $oldTokenId, int $newTokenId): void;

    public function findReplacedTokenChain(int $tokenId): array;
}
