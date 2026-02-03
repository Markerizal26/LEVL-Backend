<?php

declare(strict_types=1);

namespace Modules\Common\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Gamification\Models\Badge;

interface BadgeServiceInterface
{
    public function paginate(int $perPage = 15, array $params = []): LengthAwarePaginator;

    public function create(array $data, array $files = []): Badge;

    public function createOrFind(string $code, array $data = [], ?string $iconPath = null): Badge;

    public function find(int $id): ?Badge;

    public function update(int $id, array $data, array $files = []): ?Badge;

    public function delete(int $id): bool;
}
