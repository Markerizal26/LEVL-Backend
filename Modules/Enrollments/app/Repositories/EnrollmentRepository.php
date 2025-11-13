<?php

namespace Modules\Enrollments\Repositories;

use App\Support\FilterableRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Enrollments\Models\Enrollment;

class EnrollmentRepository
{
    use FilterableRepository;

    private array $allowedFilterFields = [
        'status',
        'course_id',
        'user_id',
        'enrolled_at',
        'completed_at',
    ];

    private array $allowedSortFields = [
        'id',
        'created_at',
        'updated_at',
        'status',
        'enrolled_at',
        'completed_at',
        'progress_percent',
    ];

    public function paginate(array $params, int $perPage = 15): LengthAwarePaginator
    {
        $query = Enrollment::query()->with(['user:id,name,email', 'course:id,slug,title,enrollment_type']);

        $this->filter($query, $params)
            ->allowFilters($this->allowedFilterFields)
            ->allowSorts($this->allowedSortFields)
            ->setDefaultSort('-created_at')
            ->setDefaultPerPage($perPage)
            ->applyFiltersAndSorting($query);

        [$page, $pageSize] = $this->getPaginationParams($params, $perPage);

        return $query->paginate($pageSize, ['*'], 'page', $page)->appends($params);
    }

    public function list(array $params): Collection
    {
        $query = Enrollment::query()->with(['user:id,name,email', 'course:id,slug,title']);

        $this->filter($query, $params)
            ->allowFilters($this->allowedFilterFields)
            ->allowSorts($this->allowedSortFields)
            ->setDefaultSort('-created_at')
            ->applyFiltersAndSorting($query);

        return $query->get();
    }

    public function paginateByCourse(int $courseId, array $params, int $perPage = 15): LengthAwarePaginator
    {
        $query = Enrollment::query()
            ->where('course_id', $courseId)
            ->with(['user:id,name,email']);

        $filteredParams = $params;
        unset($filteredParams['filter']['course_id']);

        $this->filter($query, $filteredParams)
            ->allowFilters(['status', 'user_id', 'enrolled_at', 'completed_at'])
            ->allowSorts($this->allowedSortFields)
            ->setDefaultSort('-created_at')
            ->applyFiltersAndSorting($query);

        [$page, $pageSize] = $this->getPaginationParams($params, $perPage);

        return $query->paginate($pageSize, ['*'], 'page', $page)->appends($params);
    }

    public function paginateByUser(int $userId, array $params, int $perPage = 15): LengthAwarePaginator
    {
        $query = Enrollment::query()
            ->where('user_id', $userId)
            ->with(['course:id,slug,title,status']);

        $filteredParams = $params;
        unset($filteredParams['filter']['user_id']);

        $this->filter($query, $filteredParams)
            ->allowFilters(['status', 'course_id', 'enrolled_at', 'completed_at'])
            ->allowSorts($this->allowedSortFields)
            ->setDefaultSort('-created_at')
            ->applyFiltersAndSorting($query);

        [$page, $pageSize] = $this->getPaginationParams($params, $perPage);

        return $query->paginate($pageSize, ['*'], 'page', $page)->appends($params);
    }

    public function findById(int $id): ?Enrollment
    {
        return Enrollment::query()
            ->with(['user', 'course'])
            ->find($id);
    }

    private function getPaginationParams(array $params, int $defaultPerPage): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($params['per_page'] ?? $defaultPerPage)));
        return [$page, $perPage];
    }

    public function getAllowedFilterFields(): array
    {
        return $this->allowedFilterFields;
    }

    public function getAllowedSortFields(): array
    {
        return $this->allowedSortFields;
    }
}
