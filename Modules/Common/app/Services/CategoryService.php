<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Common\Contracts\Services\CategoryServiceInterface;
use Modules\Common\DTOs\CreateCategoryDTO;
use Modules\Common\DTOs\UpdateCategoryDTO;
use Modules\Common\Models\Category;
use Modules\Common\Repositories\CategoryRepository;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryService implements CategoryServiceInterface
{
    public function __construct(private readonly CategoryRepository $repository) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = QueryBuilder::for(Category::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::partial('search'),
            ])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('name');

        return $query->paginate($perPage);
    }

    public function create(CreateCategoryDTO|array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $dto = $data instanceof CreateCategoryDTO ? $data : CreateCategoryDTO::fromRequest($data);
            return $this->repository->create($dto->toArray());
        });
    }

    public function find(int $id): ?Category
    {
        return $this->repository->find($id);
    }

    public function update(int $id, UpdateCategoryDTO|array $data): ?Category
    {
        $category = $this->repository->find($id);
        if (! $category) {
            return null;
        }

        $dto = $data instanceof UpdateCategoryDTO ? $data : UpdateCategoryDTO::fromRequest($data);
        return $this->repository->update($category, array_filter($dto->toArray(), fn ($value) => $value !== null));
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $category = $this->repository->find($id);
            if (! $category) {
                return false;
            }

            return $this->repository->delete($category);
        });
    }
}

