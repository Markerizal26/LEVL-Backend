<?php

namespace Modules\Auth\Repositories;

use Illuminate\Support\Str;
use Modules\Auth\Contracts\AuthRepositoryInterface;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\JwtRefreshToken;
use Modules\Auth\Models\User;

class AuthRepository implements AuthRepositoryInterface
{
    public function findActiveUserByLogin(string $login): ?User
    {
        return User::query()
            ->where(fn ($q) => $q->where('email', $login)->orWhere('username', $login))
            ->where('status', UserStatus::Active)
            ->first();
    }

    public function findByLogin(string $login): ?User
    {
        return User::query()
            ->where(fn ($q) => $q->where('email', $login)->orWhere('username', $login))
            ->first();
    }

    public function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => UserStatus::Pending->value,
        ]);
    }

    public function createRefreshToken(
        int $userId,
        ?string $ip,
        ?string $userAgent,
        ?string $deviceId = null,
        ?int $idleTtlDays = null,
        ?int $absoluteTtlDays = null,
    ): JwtRefreshToken {
        $token = Str::random(64);
        $idleTtlDays = $idleTtlDays ?? 14;
        $absoluteTtlDays = $absoluteTtlDays ?? 90;

        $idleExpiresAt = now()->addDays($idleTtlDays);
        $absoluteExpiresAt = now()->addDays($absoluteTtlDays);

        if (! $deviceId) {
            $deviceId = hash('sha256', ($ip ?? '').($userAgent ?? '').$userId);
        }

        return JwtRefreshToken::create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'token' => hash('sha256', $token),
            'ip' => $ip,
            'user_agent' => $userAgent,
            'last_used_at' => now(),
            'idle_expires_at' => $idleExpiresAt,
            'absolute_expires_at' => $absoluteExpiresAt,
        ])->setAttribute('plain_token', $token);
    }

    public function revokeRefreshToken(string $plainToken, int $userId): void
    {
        $hashed = hash('sha256', $plainToken);
        JwtRefreshToken::where('user_id', $userId)
            ->where('token', $hashed)
            ->valid()
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllUserRefreshTokens(int $userId): void
    {
        JwtRefreshToken::where('user_id', $userId)
            ->valid()
            ->update(['revoked_at' => now()]);
    }

    public function findValidRefreshRecordByUser(string $plainToken, int $userId): ?JwtRefreshToken
    {
        $hashed = hash('sha256', $plainToken);

        return JwtRefreshToken::query()
            ->where('user_id', $userId)
            ->where('token', $hashed)
            ->valid()
            ->first();
    }

    public function findValidRefreshRecord(string $plainToken): ?JwtRefreshToken
    {
        $hashed = hash('sha256', $plainToken);

        return JwtRefreshToken::query()->where('token', $hashed)->valid()->first();
    }

    public function revokeAllUserRefreshTokensByDevice(int $userId, string $deviceId): void
    {
        JwtRefreshToken::where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->valid()
            ->update(['revoked_at' => now()]);
    }

    public function markTokenAsReplaced(int $oldTokenId, int $newTokenId): void
    {
        JwtRefreshToken::where('id', $oldTokenId)->update(['replaced_by' => $newTokenId]);
    }

    public function findReplacedTokenChain(int $tokenId): array
    {
        $chain = [];
        $currentId = $tokenId;

        while ($currentId) {
            $token = JwtRefreshToken::find($currentId);
            if (! $token) {
                break;
            }

            $chain[] = $token;
            $currentId = $token->replaced_by;
        }

        return $chain;
    }
}
