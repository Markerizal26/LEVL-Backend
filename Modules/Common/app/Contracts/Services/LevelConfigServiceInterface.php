<?php

declare(strict_types=1);

namespace Modules\Common\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Common\Models\LevelConfig;

interface LevelConfigServiceInterface
{
    public function paginate(int $perPage = 15, array $params = []): LengthAwarePaginator;

    public function create(array $data): LevelConfig;

    public function find(int $id): ?LevelConfig;

    public function update(int $id, array $data): ?LevelConfig;

    public function delete(int $id): bool;
}
