<?php

namespace Modules\Gamification\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PointRepositoryInterface
{
    
    public function paginateByUserId(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data);

    public function sumByUserId(int $userId): int;
}
