<?php

namespace Modules\Schemes\Services;

use App\Support\CodeGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Schemes\Contracts\Repositories\UnitRepositoryInterface;
use Modules\Schemes\DTOs\CreateUnitDTO;
use Modules\Schemes\DTOs\UpdateUnitDTO;
use Modules\Schemes\Models\Unit;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UnitService
{
    public function __construct(
        private readonly UnitRepositoryInterface $repository
    ) {}

    public function paginate(int $courseId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = QueryBuilder::for(Unit::class)
            ->where('course_id', $courseId)
            ->allowedFilters([
                AllowedFilter::exact('status'),
            ])
            ->allowedIncludes(['course', 'lessons'])
            ->allowedSorts(['order', 'title', 'created_at'])
            ->defaultSort('order');

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Unit
    {
        return $this->repository->findById($id);
    }

    public function findOrFail(int $id): Unit
    {
        return $this->repository->findByIdOrFail($id);
    }

    public function create(int $courseId, CreateUnitDTO|array $data): Unit
    {
        $attributes = $data instanceof CreateUnitDTO ? $data->toArrayWithoutNull() : $data;
        $attributes['course_id'] = $courseId;

        if (empty($attributes['code'])) {
            $attributes['code'] = CodeGenerator::generate('UNIT-', 4, Unit::class);
        }

        $attributes = Arr::except($attributes, ['slug']);

        return $this->repository->create($attributes);
    }

    public function update(int $id, UpdateUnitDTO|array $data): Unit
    {
        $unit = $this->repository->findByIdOrFail($id);
        $attributes = $data instanceof UpdateUnitDTO ? $data->toArrayWithoutNull() : $data;

        $attributes = Arr::except($attributes, ['slug']);

        return $this->repository->update($unit, $attributes);
    }

    public function delete(int $id): bool
    {
        $unit = $this->repository->findByIdOrFail($id);

        return $this->repository->delete($unit);
    }

    public function reorder(int $courseId, array $data): bool
    {
        foreach ($data['units'] as $item) {
            $unitId = $item['id'];
            $newOrder = $item['order'];

            // Use repository method instead of direct model query
            $this->repository->updateOrder($unitId, $newOrder);
        }

        return true;
    }
}
