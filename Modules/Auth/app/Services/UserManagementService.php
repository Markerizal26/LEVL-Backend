<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Contracts\Services\UserManagementServiceInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Services\Support\UserFinder;
use Modules\Auth\Services\Support\UserLifecycleProcessor;

class UserManagementService implements UserManagementServiceInterface
{
    public function __construct(
        private readonly UserFinder $finder,
        private readonly UserLifecycleProcessor $lifecycleProcessor
    ) {}

    public function listUsers(User $authUser, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->finder->listUsers($authUser, $perPage, $search);
    }

    public function listUsersForIndex(User $authUser, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->finder->listUsersForIndex($authUser, $filters, $perPage);
    }

    public function showUser(User $authUser, int $userId): User
    {
        return $this->finder->showUser($authUser, $userId);
    }

    public function updateUserStatus(User $authUser, int $userId, string $status): User
    {
        return $this->lifecycleProcessor->updateUserStatus($authUser, $userId, $status);
    }

    public function deleteUser(User $authUser, int $userId): void
    {
        $this->lifecycleProcessor->deleteUser($authUser, $userId);
    }

    public function createUser(User $authUser, array $validated): User
    {
        return $this->lifecycleProcessor->createUser($authUser, $validated);
    }

    public function updateProfile(User $user, array $validated, ?string $ip, ?string $userAgent): User
    {
        return $this->lifecycleProcessor->updateProfile($user, $validated, $ip, $userAgent);
    }
}
