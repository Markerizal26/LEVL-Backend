<?php

declare(strict_types=1);

namespace Modules\Common\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Gamification\Models\Challenge;

interface ChallengeManagementServiceInterface
{
    public function paginate(int $perPage = 15, array $params = []): LengthAwarePaginator;

    public function create(array $data): Challenge;

    public function find(int $id): ?Challenge;

    public function update(int $id, array $data): ?Challenge;

    public function delete(int $id): bool;
}
