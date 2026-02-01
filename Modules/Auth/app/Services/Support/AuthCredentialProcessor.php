<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Support;

use App\Jobs\CreateAuditJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;

class AuthCredentialProcessor
{
    public function setUsername(User $user, string $username): array
    {
        $user->update(['username' => $username]);
        $user->refresh();

        Cache::tags(['user_profile'])->forget('user_profile:'.$user->id);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();
        $userArray['avatar_url'] = $user->avatar_url;

        return ['user' => $userArray];
    }

    public function setPassword(User $user, string $password): array
    {
        $user->update([
            'password' => Hash::make($password),
            'is_password_set' => true,
        ]);
        $user->refresh();

        Cache::tags(['user_profile'])->forget('user_profile:'.$user->id);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();
        $userArray['avatar_url'] = $user->avatar_url;

        return ['user' => $userArray];
    }

    public function logProfileUpdate(
        User $user,
        array $changes,
        ?string $ip,
        ?string $userAgent,
    ): void {
        dispatch(new CreateAuditJob([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'meta' => ['action' => 'profile.update', 'changes' => $changes],
            'logged_at' => now(),
        ]));
    }

    public function logEmailChangeRequest(
        User $user,
        string $newEmail,
        string $uuid,
        ?string $ip,
        ?string $userAgent,
    ): void {
        dispatch(new CreateAuditJob([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'meta' => [
                'action' => 'email.change.request',
                'new_email' => $newEmail,
                'uuid' => $uuid,
            ],
            'logged_at' => now(),
        ]));
    }
}
