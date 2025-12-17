<?php

namespace Modules\Auth\Repositories;

use Illuminate\Support\Collection;
use Modules\Auth\Contracts\UserBulkRepositoryInterface;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;

class UserBulkRepository implements UserBulkRepositoryInterface
{
  public function findByIds(array $userIds): Collection
  {
    return User::with("roles")->whereIn("id", $userIds)->get();
  }

  public function bulkUpdateStatus(array $userIds, string $status): int
  {
    return User::whereIn("id", $userIds)->update(["status" => UserStatus::from($status)]);
  }

  public function bulkDelete(array $userIds): int
  {
    return User::whereIn("id", $userIds)->delete();
  }
}
