<?php

namespace Modules\Auth\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Models\JwtRefreshToken;
use Modules\Auth\Models\User;
use Modules\Auth\Interfaces\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    public function findActiveUserByLogin(string $login): ?User
    {
        $query = User::query()
            ->where(fn ($q) => $q->where('email', $login)->orWhere('username', $login))
            ->where('status', 'active');

        return $query->first();
    }

    public function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => 'active',
        ]);
    }

    public function createRefreshToken(int $userId, ?string $ip, ?string $userAgent, ?int $ttlMinutes = null): JwtRefreshToken
    {
        $token = Str::random(64);
        $expiresAt = $ttlMinutes ? now()->addMinutes($ttlMinutes) : null;

        return JwtRefreshToken::create([
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'ip' => $ip,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt,
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
        JwtRefreshToken::where('user_id', $userId)->valid()->update(['revoked_at' => now()]);
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
}


