<?php

namespace Modules\Gamification\Contracts\Repositories;

use Illuminate\Support\Collection;

interface UserBadgeRepositoryInterface
{
    
    public function countByUserId(int $userId): int;

    public function findByUserId(int $userId): Collection;

    public function create(array $data);
}
