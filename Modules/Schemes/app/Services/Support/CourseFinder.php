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
        private readonly SchemesCacheService $cacheService,
        private readonly CourseIncludeAuthorizer $includeAuthorizer
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $user = auth('api')->user();
        $userKey = $user ? $user->id.':'.implode(',', $user->getRoleNames()->toArray()) : 'guest';
        $includeParam = request()->get('include', '');
        $cacheKey = "schemes:courses:paginate:{$perPage}:{$userKey}:".request('page', 1).':'.md5(json_encode($filters).':'.$includeParam);

        if (! empty($includeParam)) {
            return $this->buildQuery($filters)->paginate($perPage);
        }

        return \Illuminate\Support\Facades\Cache::tags(['schemes', 'courses'])->remember(
            $cacheKey,
            300,
            function () use ($filters, $perPage) {
                return $this->buildQuery($filters)->paginate($perPage);
            }
        );
    }

    public function paginateForIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        if ($this->shouldBypassListingCache()) {
            return $this->buildQueryForIndex($filters)->paginate($perPage);
        }

        $user = auth('api')->user();
        $userKey = $user ? $user->id.':'.implode(',', $user->getRoleNames()->toArray()) : 'guest';
        $includeParam = request()->get('include', '');
        $cacheKey = "schemes:courses:index:paginate:{$perPage}:{$userKey}:".request('page', 1).':'.md5(json_encode($filters).':'.$includeParam);

        if (! empty($includeParam)) {
            return $this->buildQueryForIndex($filters)->paginate($perPage);
        }

        return \Illuminate\Support\Facades\Cache::tags(['schemes', 'courses'])->remember(
            $cacheKey,
            300,
            function () use ($filters, $perPage) {
                return $this->buildQueryForIndex($filters)->paginate($perPage);
            }
        );
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

    public function listAll(array $filters = [])
    {
        if (data_get($filters, 'status') === 'published') {
            return $this->buildQueryForIndex($filters)
                ->where('status', 'published')
                ->get();
        }

        return $this->buildQueryForIndex($filters)->get();
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

        if ($this->shouldBypassListingCache()) {
            return $this->buildQueryForIndex($filters)
                ->where('status', 'published')
                ->paginate($perPage);
        }

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

    public function findBySlugWithIncludes(string $slug): ?Course
    {
        $request = request();
        $includeParam = $request->get('include', '');
        $user = auth('api')->user();

        $baseQuery = Course::where('slug', $slug);

        if (empty($includeParam)) {
            if ($user) {
                $baseQuery->with(['enrollments' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }]);
            }

            return $baseQuery->first();
        }

        $course = Course::where('slug', $slug)->first();
        if (! $course) {
            return null;
        }

        $requestedIncludes = array_filter(explode(',', $includeParam));
        if (in_array('elements', $requestedIncludes)) {
            $requestedIncludes = array_diff($requestedIncludes, ['elements']);
            if (! in_array('units', $requestedIncludes)) {
                $requestedIncludes[] = 'units';
            }
            $request->merge(['include' => implode(',', $requestedIncludes)]);
        }

        $allowedIncludes = $this->includeAuthorizer->getAllowedIncludesForQueryBuilder($user, $course);

        $queryBuilder = QueryBuilder::for(Course::class, $request)
            ->where('slug', $slug)
            ->allowedIncludes($allowedIncludes);

        if ($user) {
            $queryBuilder->with(['enrollments' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);
        }

        return $queryBuilder->first();
    }

    private function shouldBypassListingCache(): bool
    {
        return \App\Support\LoadtestBypass::isEnabled();
    }

    private function buildQuery(array $filters = []): QueryBuilder
    {
        $searchQuery = data_get($filters, 'search');

        $cleanFilters = Arr::except($filters, ['search', 'tag']);
        $request = new Request($cleanFilters);

        $builder = QueryBuilder::for(
            Course::query(),
            $request
        );

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $builder->search($searchQuery);
        }

        $user = auth('api')->user();
        if ($user && $user->hasRole('Instructor') && ! $user->hasRole('Superadmin') && ! $user->hasRole('Admin')) {
            $builder->where(function ($q) use ($user) {
                $q->where('status', 'published')
                    ->orWhereHas('instructors', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
            });
        }

        if ($tagFilter = data_get($filters, 'tag')) {
            $this->applyTagFilter($builder, $tagFilter);
        }

        return $builder
            ->with('instructor')
            ->withCount(['enrollments'])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('level_tag'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('category_id'),
            ])
            ->allowedIncludes($this->includeAuthorizer->getAllowedIncludesForIndex(auth('api')->user()))
            ->allowedSorts(['id', 'code', 'title', 'created_at', 'updated_at', 'published_at'])
            ->defaultSort('title');
    }

    private function buildQueryForIndex(array $filters = []): QueryBuilder
    {
        $searchQuery = data_get($filters, 'search');

        $cleanFilters = Arr::except($filters, ['search', 'tag']);
        $request = new Request($cleanFilters);

        $builder = QueryBuilder::for(
            Course::query(),
            $request
        );

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $builder->search($searchQuery);
        }

        $user = auth('api')->user();
        if ($user && $user->hasRole('Instructor') && ! $user->hasRole('Superadmin') && ! $user->hasRole('Admin')) {
            $builder->where(function ($q) use ($user) {
                $q->where('status', 'published')
                    ->orWhereHas('instructors', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
            });
        }

        if ($tagFilter = data_get($filters, 'tag')) {
            $this->applyTagFilter($builder, $tagFilter);
        }

        $user = auth('api')->user();
        if ($user) {
            $builder->with(['enrollments' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }]);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('level_tag'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('category_id'),
            ])
            ->allowedIncludes($this->includeAuthorizer->getAllowedIncludesForIndex(auth('api')->user()))
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

    public function searchGlobal(string $query, int $limit = 5): \Illuminate\Support\Collection
    {
        if (empty(trim($query))) {
            return collect();
        }

        return Course::search($query)
            ->with(['media'])
            ->limit($limit)
            ->get();
    }

    public function listEnrolledCourses(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $status = data_get($filters, 'filter.status');
        $searchQuery = data_get($filters, 'search');

        $cleanFilters = Arr::except($filters, ['filter.status', 'search']);
        $request = new Request($cleanFilters);

        $builder = QueryBuilder::for(Course::class, $request)
            ->whereHas('enrollments', function ($query) use ($userId, $status) {
                $query->where('user_id', $userId);

                if ($status === 'active') {
                    $query->where('status', \Modules\Enrollments\Enums\EnrollmentStatus::Active);
                } elseif ($status === 'completed') {
                    $query->where('status', \Modules\Enrollments\Enums\EnrollmentStatus::Completed);
                } elseif ($status === 'pending') {
                    $query->where('status', \Modules\Enrollments\Enums\EnrollmentStatus::Pending);
                } else {
                    $query->whereIn('status', [
                        \Modules\Enrollments\Enums\EnrollmentStatus::Active,
                        \Modules\Enrollments\Enums\EnrollmentStatus::Completed,
                        \Modules\Enrollments\Enums\EnrollmentStatus::Pending,
                    ]);
                }
            })
            ->with(['enrollments' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->allowedFilters([
                AllowedFilter::exact('level_tag'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('category_id'),
            ])
            ->allowedIncludes($this->includeAuthorizer->getAllowedIncludesForIndex(auth('api')->user()))
            ->allowedSorts(['title', 'created_at', 'updated_at'])
            ->defaultSort('-updated_at');

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $builder->search($searchQuery);
        }

        return $builder->paginate($perPage);
    }

    public function findBySlugWithFilteredIncludes(string $slug, array $includes): ?Course
    {
        $user = auth('api')->user();

        $query = Course::where('slug', $slug);

        if (! empty($includes)) {
            $query->with($includes);
        }

        if ($user) {
            $query->with(['enrollments' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);
        }

        return $query->first();
    }
}
