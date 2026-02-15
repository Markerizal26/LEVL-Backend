<?php

declare(strict_types=1);

namespace Modules\Enrollments\Services\Support;

use App\Exceptions\BusinessException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Modules\Auth\Models\User;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class EnrollmentFinder
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $repository
    ) {}

    public function paginateByCourse(int $courseId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return cache()->tags(['enrollments', "course:{$courseId}"])->remember(
            "enrollments:course:{$courseId}:{$perPage}:".md5(json_encode($filters)),
            300,
            function () use ($courseId, $filters, $perPage) {
                return $this->buildQuery(
                    QueryBuilder::for(Enrollment::class, $this->makeRequest($filters))->where('course_id', $courseId),
                    $filters,
                    $perPage
                );
            }
        );
    }

    public function paginateByCourseForIndex(int $courseId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return cache()->tags(['enrollments', "course:{$courseId}"])->remember(
            "enrollments:course:index:{$courseId}:{$perPage}:".md5(json_encode($filters)),
            300,
            function () use ($courseId, $filters, $perPage) {
                return $this->buildQueryForIndex(
                    QueryBuilder::for(Enrollment::class, $this->makeRequest($filters))->where('course_id', $courseId),
                    $filters,
                    $perPage
                );
            }
        );
    }

    public function paginateByCourseIds(array $courseIds, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        sort($courseIds);
        $idsHash = md5(json_encode($courseIds));

        return cache()->tags(['enrollments', 'course_ids'])->remember(
            "enrollments:courses:ids:{$idsHash}:{$perPage}:".md5(json_encode($filters)),
            300,
            function () use ($courseIds, $filters, $perPage) {
                return $this->buildQuery(
                    QueryBuilder::for(Enrollment::class, $this->makeRequest($filters))->whereIn('course_id', $courseIds),
                    $filters,
                    $perPage
                );
            }
        );
    }

    public function paginateByCourseIdsForIndex(array $courseIds, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        sort($courseIds);
        $idsHash = md5(json_encode($courseIds));

        return cache()->tags(['enrollments', 'course_ids'])->remember(
            "enrollments:courses:index:ids:{$idsHash}:{$perPage}:".md5(json_encode($filters)),
            300,
            function () use ($courseIds, $filters, $perPage) {
                return $this->buildQueryForIndex(
                    QueryBuilder::for(Enrollment::class, $this->makeRequest($filters))->whereIn('course_id', $courseIds),
                    $filters,
                    $perPage
                );
            }
        );
    }

    public function paginateByUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return cache()->tags(['enrollments', "user:{$userId}"])->remember(
            "enrollments:user:{$userId}:{$perPage}:".md5(json_encode($filters)),
            300,
            function () use ($userId, $filters, $perPage) {
                return $this->buildQuery(
                    QueryBuilder::for(Enrollment::class, $this->makeRequest($filters))->where('user_id', $userId),
                    $filters,
                    $perPage,
                    false // use default sort for user
                );
            }
        );
    }

    public function paginateByUserForIndex(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return cache()->tags(['enrollments', "user:{$userId}"])->remember(
            "enrollments:user:index:{$userId}:{$perPage}:".md5(json_encode($filters)),
            300,
            function () use ($userId, $filters, $perPage) {
                return $this->buildQueryForIndex(
                    QueryBuilder::for(Enrollment::class, $this->makeRequest($filters))->where('user_id', $userId),
                    $filters,
                    $perPage,
                    false
                );
            }
        );
    }

    public function paginateAll(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return cache()->tags(['enrollments'])->remember(
            "enrollments:all:{$perPage}:".md5(json_encode($filters)),
            300,
            function () use ($filters, $perPage) {
                return $this->buildQuery(
                    QueryBuilder::for(Enrollment::class, $this->makeRequest($filters)),
                    $filters,
                    $perPage
                );
            }
        );
    }

    public function paginateAllForIndex(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return cache()->tags(['enrollments'])->remember(
            "enrollments:all:index:{$perPage}:".md5(json_encode($filters)),
            300,
            function () use ($filters, $perPage) {
                return $this->buildQueryForIndex(
                    QueryBuilder::for(Enrollment::class, $this->makeRequest($filters)),
                    $filters,
                    $perPage
                );
            }
        );
    }

    public function getManagedEnrollments(User $user, int $perPage = 15, ?string $courseSlug = null, array $filters = []): array
    {
        $courses = $this->getManagedCourses($user);
        $courseIds = $courses->pluck('id')->all();

        if ($courseSlug) {
            $course = $courses->firstWhere('slug', $courseSlug);
            if (! $course) {
                return ['found' => false, 'paginator' => null];
            }
            $paginator = $this->paginateByCourse($course->id, $perPage, $filters);
        } else {
            $paginator = $this->paginateByCourseIds($courseIds, $perPage, $filters);
        }

        return ['found' => true, 'paginator' => $paginator];
    }

    public function getManagedEnrollmentsForIndex(User $user, int $perPage = 15, ?string $courseSlug = null, array $filters = []): array
    {
        $courses = $this->getManagedCourses($user);
        $courseIds = $courses->pluck('id')->all();

        if ($courseSlug) {
            $course = $courses->firstWhere('slug', $courseSlug);
            if (! $course) {
                return ['found' => false, 'paginator' => null];
            }
            $paginator = $this->paginateByCourseForIndex($course->id, $perPage, $filters);
        } else {
            $paginator = $this->paginateByCourseIdsForIndex($courseIds, $perPage, $filters);
        }

        return ['found' => true, 'paginator' => $paginator];
    }

    public function findById(int $id): ?Enrollment
    {
        return $this->repository->findById($id);
    }

    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment
    {
        return $this->repository->findByCourseAndUser($courseId, $userId);
    }

    public function isUserEnrolledInCourse(int $userId, int $courseId): bool
    {
        return $this->repository->hasActiveEnrollment($userId, $courseId);
    }

    public function getActiveEnrollment(int $userId, int $courseId): ?Enrollment
    {
        return $this->repository->getActiveEnrollment($userId, $courseId);
    }

    public function listEnrollments(User $user, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $courseSlug = $filters['filter']['course_slug'] ?? $filters['course_slug'] ?? null;

        if ($user->hasRole('Superadmin')) {
            return $this->paginateAll($perPage, $filters);
        }

        if ($user->hasRole(['Admin', 'Instructor'])) {
            $result = $this->getManagedEnrollments($user, $perPage, $courseSlug, $filters);

            if (! $result['found']) {
                throw new BusinessException(__('messages.enrollments.course_not_managed'), []);
            }

            return $result['paginator'];
        }

        return $this->paginateByUser($user->id, $perPage, $filters);
    }

    public function listEnrollmentsForIndex(User $user, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $courseSlug = $filters['filter']['course_slug'] ?? $filters['course_slug'] ?? null;

        if ($user->hasRole('Superadmin')) {
            return $this->paginateAllForIndex($perPage, $filters);
        }

        if ($user->hasRole(['Admin', 'Instructor'])) {
            $result = $this->getManagedEnrollmentsForIndex($user, $perPage, $courseSlug, $filters);

            if (! $result['found']) {
                throw new BusinessException(__('messages.enrollments.course_not_managed'), []);
            }

            return $result['paginator'];
        }

        return $this->paginateByUserForIndex($user->id, $perPage, $filters);
    }

    public function findEnrollmentForAction(Course $course, User $user, array $data): Enrollment
    {
        $targetUserId = $user->hasRole('Superadmin') && isset($data['user_id'])
            ? (int) $data['user_id']
            : $user->id;

        $enrollment = $this->findByCourseAndUser($course->id, $targetUserId);

        if (! $enrollment) {
            throw new BusinessException(__('messages.enrollments.not_found'), []);
        }

        return $enrollment;
    }

    public function getEnrollmentStatus(Course $course, User $user, array $data): array
    {
        $targetUserId = $user->hasRole('Superadmin') && isset($data['user_id'])
            ? (int) $data['user_id']
            : $user->id;

        $enrollment = $this->findByCourseAndUser($course->id, $targetUserId);

        if ($enrollment) {
            $enrollment = $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
        }

        return [
            'found' => (bool) $enrollment,
            'enrollment' => $enrollment,
        ];
    }

    private function makeRequest(array $filters): Request
    {
        $cleanFilters = Arr::except($filters, ['search']);

        return new Request($cleanFilters);
    }

    private function buildQuery(QueryBuilder $builder, array $filters, int $perPage, bool $usePrioritySort = true): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $searchQuery = trim((string) (data_get($filters, 'search') ?? data_get($filters, 'filter.search') ?? ''));

        if ($searchQuery !== '') {
            $term = "%{$searchQuery}%";
            $builder->where(function ($query) use ($term) {
                $query->whereHas('user', function ($q) use ($term) {
                    $q->where('name', 'ILIKE', $term)
                        ->orWhere('email', 'ILIKE', $term)
                        ->orWhere('username', 'ILIKE', $term);
                })->orWhereHas('course', function ($q) use ($term) {
                    $q->where('title', 'ILIKE', $term);
                });
            });
        }

        $builder->with(['user' => fn ($q) => $q->select('id', 'name', 'email')->with('media'), 'course'])
            ->allowedFilters($this->getAllowedFilters())
            ->allowedIncludes(['user', 'course']);

        $userNameSort = $this->makeUserNameSort();
        $courseNameSort = $this->makeCourseTitleSort();
        $statusSort = AllowedSort::field('status', 'status');

        if ($usePrioritySort) {
            $prioritySort = $this->getPrioritySort();
            $builder->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $prioritySort,
                $userNameSort,
                $courseNameSort,
                $statusSort,
            ])->defaultSort($prioritySort);
        } else {
            $builder->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $userNameSort,
                $courseNameSort,
                $statusSort,
            ])->defaultSort('-enrolled_at');
        }

        return $builder->paginate($perPage);
    }

    private function buildQueryForIndex(QueryBuilder $builder, array $filters, int $perPage, bool $usePrioritySort = true): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $searchQuery = trim((string) (data_get($filters, 'search') ?? data_get($filters, 'filter.search') ?? ''));

        if ($searchQuery !== '') {
            $term = "%{$searchQuery}%";
            $builder->where(function ($query) use ($term) {
                $query->whereHas('user', function ($q) use ($term) {
                    $q->where('name', 'ILIKE', $term)
                        ->orWhere('email', 'ILIKE', $term)
                        ->orWhere('username', 'ILIKE', $term);
                })->orWhereHas('course', function ($q) use ($term) {
                    $q->where('title', 'ILIKE', $term);
                });
            });
        }

        $builder->with(['user' => fn ($q) => $q->select('id', 'name', 'email')->with('media'), 'course:id,title,slug,code'])
            ->allowedFilters($this->getAllowedFilters());

        $userNameSort = $this->makeUserNameSort();
        $courseNameSort = $this->makeCourseTitleSort();
        $statusSort = AllowedSort::field('status', 'status');

        if ($usePrioritySort) {
            $prioritySort = $this->getPrioritySort();
            $builder->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $prioritySort,
                $userNameSort,
                $courseNameSort,
                $statusSort,
            ])->defaultSort($prioritySort);
        } else {
            $builder->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $userNameSort,
                $courseNameSort,
                $statusSort,
            ])->defaultSort('-enrolled_at');
        }

        return $builder->paginate($perPage);
    }

    private function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
            AllowedFilter::exact('user_id'),
            AllowedFilter::callback('course_slug', function ($query, $value) {
                $course = Course::where('slug', $value)->first();
                if ($course) {
                    $query->where('course_id', $course->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }),
            AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
            AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
        ];
    }

    private function makeUserNameSort(): AllowedSort
    {
        $usersTable = (new Enrollment)->user()->getRelated()->getTable();
        $enrollmentsTable = (new Enrollment)->getTable();

        return AllowedSort::callback('user_name', function ($query, $descending) use ($usersTable, $enrollmentsTable) {
            $query->select("{$enrollmentsTable}.*")
                ->leftJoin($usersTable, "{$enrollmentsTable}.user_id", '=', "{$usersTable}.id")
                ->orderBy("{$usersTable}.name", $descending ? 'desc' : 'asc');
        });
    }

    private function makeCourseTitleSort(): AllowedSort
    {
        $coursesTable = (new Enrollment)->course()->getRelated()->getTable();
        $enrollmentsTable = (new Enrollment)->getTable();

        return AllowedSort::callback('course_title', function ($query, $descending) use ($coursesTable, $enrollmentsTable) {
            $query->select("{$enrollmentsTable}.*")
                ->leftJoin($coursesTable, "{$enrollmentsTable}.course_id", '=', "{$coursesTable}.id")
                ->orderBy("{$coursesTable}.title", $descending ? 'desc' : 'asc');
        });
    }

    private function getPrioritySort(): AllowedSort
    {
        return AllowedSort::callback('priority', function ($query, $descending) {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                ->orderBy('created_at', 'desc');
        });
    }

    private function getManagedCourses(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return Course::query()
            ->select(['id', 'slug', 'title'])
            ->where(function ($query) use ($user) {
                $query
                    ->where('instructor_id', $user->id)
                    ->orWhereHas('admins', function ($adminQuery) use ($user) {
                        $adminQuery->where('user_id', $user->id);
                    });
            })
            ->get();
    }
}
