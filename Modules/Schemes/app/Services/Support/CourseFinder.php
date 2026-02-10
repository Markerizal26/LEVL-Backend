<?php

declare(strict_types=1);

namespace Modules\Schemes\Services\Support;

use App\Support\Helpers\ArrayParser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Schemes\Contracts\Repositories\CourseRepositoryInterface;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Services\SchemesCacheService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseFinder
{
    use \App\Support\Traits\BuildsQueryBuilderRequest;

    public function __construct(
        private readonly CourseRepositoryInterface $repository,
        private readonly SchemesCacheService $cacheService
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        return $this->buildQuery($filters)->paginate($perPage);
    }

    public function paginateForIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        return $this->buildQueryForIndex($filters)->paginate($perPage);
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (data_get($filters, 'status') === 'published') {
            return $this->listPublic($perPage, $filters);
        }

        return $this->paginate($filters, $perPage);
    }

    public function listForIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (data_get($filters, 'status') === 'published') {
            return $this->listPublicForIndex($perPage, $filters);
        }

        return $this->paginateForIndex($filters, $perPage);
    }

    public function listPublic(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $page = request()->get('page', 1);

        return $this->cacheService->getPublicCourses($page, $perPage, $filters, function () use ($filters, $perPage) {
            return $this->buildQuery($filters)
                ->where('status', 'published')
                ->paginate($perPage);
        });
    }

    public function listPublicForIndex(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $page = request()->get('page', 1);

        return $this->cacheService->getPublicCoursesForIndex($page, $perPage, $filters, function () use ($filters, $perPage) {
            return $this->buildQueryForIndex($filters)
                ->where('status', 'published')
                ->paginate($perPage);
        });
    }

    public function find(int $id): ?Course
    {
        return $this->cacheService->getCourse($id);
    }

    public function findOrFail(int $id): Course
    {
        $course = $this->cacheService->getCourse($id);

        if (! $course) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException;
        }

        return $course;
    }

    public function findBySlug(string $slug): ?Course
    {
        return $this->cacheService->getCourseBySlug($slug);
    }

    private function buildQuery(array $filters = []): QueryBuilder
    {
        $searchQuery = data_get($filters, 'search');
        
        $cleanFilters = Arr::except($filters, ['search', 'tag']);
        $request = new Request($cleanFilters);

        $builder = QueryBuilder::for(
            Course::with(['instructor.media', 'instructor.roles', 'admins.media', 'admins.roles', 'media']),
            $request
        );

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $builder->search($searchQuery);
        }

        if ($tagFilter = data_get($filters, 'tag')) {
            $this->applyTagFilter($builder, $tagFilter);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('level_tag'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('category_id'),
            ])
            ->allowedIncludes(['tags', 'category', 'instructor', 'units', 'admins'])
            ->allowedSorts(['id', 'code', 'title', 'created_at', 'updated_at', 'published_at'])
            ->defaultSort('title');
    }

    private function buildQueryForIndex(array $filters = []): QueryBuilder
    {
        $searchQuery = data_get($filters, 'search');
        
        $cleanFilters = Arr::except($filters, ['search', 'tag']);
        $request = new Request($cleanFilters);

        $builder = QueryBuilder::for(
            Course::with([
                'admins:id,name,username,email,status,account_status',
                'media:id,model_type,model_id,collection_name,file_name,disk,updated_at',
            ])->withCount('admins'),
            $request
        );

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $builder->search($searchQuery);
        }

        if ($tagFilter = data_get($filters, 'tag')) {
            $this->applyTagFilter($builder, $tagFilter);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('level_tag'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('category_id'),
            ])
            ->allowedIncludes(['tags'])
            ->allowedSorts(['id', 'code', 'title', 'created_at', 'updated_at', 'published_at'])
            ->defaultSort('title');
    }

    private function applyTagFilter(QueryBuilder $builder, mixed $tagFilter): void
    {
        $tags = ArrayParser::parseFilter($tagFilter);
        foreach ($tags as $tagValue) {
            $value = trim((string) $tagValue);
            if ($value === '') {
                continue;
            }
            $slug = Str::slug($value);
            $builder->whereHas('tags', fn ($q) => $q->where(fn ($iq) => $iq->where('slug', $slug)->orWhere('slug', $value)->orWhereRaw('LOWER(name) = ?', [mb_strtolower($value)])));
        }
    }
}
