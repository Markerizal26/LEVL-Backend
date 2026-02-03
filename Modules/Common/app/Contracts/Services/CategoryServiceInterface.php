<?php

declare(strict_types=1);

namespace Modules\Common\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Common\DTOs\CreateCategoryDTO;
use Modules\Common\DTOs\UpdateCategoryDTO;
use Modules\Common\Models\Category;

interface CategoryServiceInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function create(CreateCategoryDTO|array $data): Category;

    public function find(int $id): ?Category;

    public function update(int $id, UpdateCategoryDTO|array $data): ?Category;

    public function delete(int $id): bool;
}
