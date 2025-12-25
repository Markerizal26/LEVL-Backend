<?php

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\Repositories\UserBulkRepositoryInterface;
use Modules\Auth\Contracts\Services\UserBulkServiceInterface;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Jobs\ExportUsersToEmailJob;
use Modules\Auth\Models\User;

class UserBulkService implements UserBulkServiceInterface
{
    public function __construct(private UserBulkRepositoryInterface $repository) {}

    public function exportToEmail(array $userIds, string $recipientEmail): void
    {
        ExportUsersToEmailJob::dispatch($userIds, $recipientEmail);
    }

    public function bulkActivate(array $userIds, int $changedBy): int
    {
        $updated = $this->repository->bulkUpdateStatus($userIds, UserStatus::Active->value);

        $this->logStatusChanges($userIds, $changedBy, UserStatus::Active->value);

        return $updated;
    }

    public function bulkDeactivate(array $userIds, int $changedBy, int $currentUserId): int
    {
        $userIds = array_diff($userIds, [$currentUserId]);

        if (empty($userIds)) {
            throw new \InvalidArgumentException(__('messages.auth.cannot_deactivate_self'));
        }

        $updated = $this->repository->bulkUpdateStatus($userIds, UserStatus::Inactive->value);

        $this->logStatusChanges($userIds, $changedBy, UserStatus::Inactive->value);

        return $updated;
    }

    public function bulkDelete(array $userIds, int $currentUserId): int
    {
        $userIds = array_diff($userIds, [$currentUserId]);

        if (empty($userIds)) {
            throw new \InvalidArgumentException(__('messages.auth.cannot_delete_self'));
        }

        return $this->repository->bulkDelete($userIds);
    }

    private function logStatusChanges(array $userIds, int $changedBy, string $newStatus): void
    {
        foreach ($userIds as $userId) {
            $targetUser = User::find($userId);
            if ($targetUser) {
                $targetUser->logActivity('status_changed', [
                    'changed_by' => $changedBy,
                    'old_status' => $targetUser->status->value,
                    'new_status' => $newStatus,
                ]);
            }
        }
    }
}
