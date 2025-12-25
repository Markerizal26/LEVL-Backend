<?php

namespace Modules\Auth\Contracts\Services;

interface UserBulkServiceInterface
{
    public function exportToEmail(array $userIds, string $recipientEmail): void;

    public function bulkActivate(array $userIds, int $changedBy): int;

    public function bulkDeactivate(array $userIds, int $changedBy, int $currentUserId): int;

    public function bulkDelete(array $userIds, int $currentUserId): int;
}
