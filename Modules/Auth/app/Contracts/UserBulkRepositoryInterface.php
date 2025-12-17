<?php

namespace Modules\Auth\Contracts;

use Illuminate\Support\Collection;

interface UserBulkRepositoryInterface
{
  public function findByIds(array $userIds): Collection;

  public function bulkUpdateStatus(array $userIds, string $status): int;

  public function bulkDelete(array $userIds): int;
}
