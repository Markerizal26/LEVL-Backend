<?php

namespace Modules\Schemes\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Schemes\Contracts\Repositories\UnitRepositoryInterface;
use Modules\Schemes\Models\Unit;

class UnitRepository extends BaseRepository implements UnitRepositoryInterface
{
    /**
     * Allowed filter keys.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [
        'status',
    ];

    /**
     * Allowed sort fields.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'id',
        'code',
        'title',
        'order',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * Default sort field.
     */
    protected string $defaultSort = 'order';

    protected function model(): string
    {
        return Unit::class;
    }

    public function findByCourse(int $courseId, array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()->where('course_id', $courseId);

        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Unit::search($searchQuery)
                ->query(fn ($q) => $q->where('course_id', $courseId))
                ->keys()
                ->toArray();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $this->filteredPaginate(
            $query,
            $params,
            $this->allowedFilters,
            $this->allowedSorts,
            $this->defaultSort,
            $perPage
        );
    }

    public function findByCourseAndId(int $courseId, int $id): ?Unit
    {
        return $this->query()->where('course_id', $courseId)->find($id);
    }

    public function getMaxOrderForCourse(int $courseId): int
    {
        return (int) Unit::where('course_id', $courseId)->max('order') ?? 0;
    }

    public function reorderUnits(int $courseId, array $unitOrders): void
    {
        foreach ($unitOrders as $unitId => $order) {
            Unit::where('course_id', $courseId)
                ->where('id', $unitId)
                ->update(['order' => (int) $order]);
        }
    }

    public function getAllByCourse(int $courseId): Collection
    {
        return $this->query()
            ->where('course_id', $courseId)
            ->orderBy('order', 'asc')
            ->get();
    }

    public function updateOrder(int $unitId, int $order): bool
    {
        return (bool) $this->query()
            ->where('id', $unitId)
            ->update(['order' => (int) $order]);
    }
}
