<?php

declare(strict_types=1);

namespace Modules\Enrollments\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Models\Enrollment;

class EnrollmentRepository extends BaseRepository implements EnrollmentRepositoryInterface
{
    protected const CACHE_TTL_ROSTER = 1800;

    protected const CACHE_PREFIX_ENROLLMENT = 'enrollment:';

    protected const CACHE_PREFIX_ROSTER = 'roster:';

    protected array $allowedFilters = [
        'status',
        'user_id',
        'course_id',
        'enrolled_at',
        'completed_at',
    ];

    protected array $allowedSorts = [
        'id',
        'created_at',
        'updated_at',
        'status',
        'enrolled_at',
        'completed_at',
        'progress_percent',
    ];

    protected string $defaultSort = '-created_at';

    protected array $with = ['user:id,name,email', 'course:id,slug,title,enrollment_type'];

    protected function model(): string
    {
        return Enrollment::class;
    }

    public function paginateByCourse(int $courseId, array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('course_id', $courseId)
            ->with(['user:id,name,email']);

        $searchQuery = $params['search'] ?? null;

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)
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

    public function paginateByCourseIds(array $courseIds, array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()
            ->with(['user:id,name,email', 'course:id,slug,title,enrollment_type']);

        if (! empty($courseIds)) {
            $query->whereIn('course_id', $courseIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        $searchQuery = $params['search'] ?? null;

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)
                ->query(fn ($q) => $q->whereIn('course_id', $courseIds))
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

    public function paginateByUser(int $userId, array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with(['course:id,slug,title,status']);

        $searchQuery = $params['search'] ?? null;

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)
                ->query(fn ($q) => $q->where('user_id', $userId))
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

    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment
    {
        $cacheKey = $this->getEnrollmentCacheKey($courseId, $userId);

        return Cache::tags(['enrollments', "course:{$courseId}"])
            ->remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($courseId, $userId) {
                return $this->query()
                    ->withoutEagerLoads()
                    ->where('course_id', $courseId)
                    ->where('user_id', $userId)
                    ->first();
            });
    }

    public function hasActiveEnrollment(int $userId, int $courseId): bool
    {
        $cacheKey = $this->getActiveEnrollmentCacheKey($userId, $courseId);

        return Cache::tags(['enrollments', "course:{$courseId}"])
            ->remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($userId, $courseId) {
                return $this->query()
                    ->where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->whereIn('status', [\Modules\Enrollments\Enums\EnrollmentStatus::Active->value, \Modules\Enrollments\Enums\EnrollmentStatus::Completed->value])
                    ->exists();
            });
    }

    public function getActiveEnrollment(int $userId, int $courseId): ?Enrollment
    {
        $cacheKey = $this->getActiveEnrollmentDetailCacheKey($userId, $courseId);

        return Cache::tags(['enrollments', "course:{$courseId}"])
            ->remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($userId, $courseId) {
                return $this->query()
                    ->where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->whereIn('status', [\Modules\Enrollments\Enums\EnrollmentStatus::Active->value, \Modules\Enrollments\Enums\EnrollmentStatus::Completed->value])
                    ->first();
            });
    }

    public function findActiveByUserAndCourse(int $userId, int $courseId): ?Enrollment
    {
        return $this->getActiveEnrollment($userId, $courseId);
    }

    public function incrementLessonProgress(int $enrollmentId, int $lessonId): void
    {
        $progress = \Modules\Enrollments\Models\LessonProgress::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('lesson_id', $lessonId)
            ->first();

        if ($progress) {
            $progress->increment('attempt_count');
        } else {
            \Modules\Enrollments\Models\LessonProgress::create([
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
                'status' => \Modules\Enrollments\Enums\ProgressStatus::NotStarted,
                'progress_percent' => 0,
                'attempt_count' => 1,
            ]);
        }

        $enrollment = Enrollment::find($enrollmentId);
        if ($enrollment) {
            $this->invalidateEnrollmentCache($enrollment->course_id, $enrollment->user_id);
            Cache::tags(['enrollments', 'progress'])->forget($this->getProgressCacheKey($enrollmentId));
        }
    }

    public function getCourseProgress(int $enrollmentId): float
    {
        $cacheKey = $this->getProgressCacheKey($enrollmentId);

        return Cache::tags(['enrollments', 'progress'])
            ->remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($enrollmentId) {
                $progress = \Modules\Enrollments\Models\CourseProgress::where('enrollment_id', $enrollmentId)
                    ->value('progress_percent');

                return (float) ($progress ?? 0);
            });
    }

    protected function getProgressCacheKey(int $enrollmentId): string
    {
        return self::CACHE_PREFIX_ENROLLMENT."progress:{$enrollmentId}";
    }

    protected function getEnrollmentCacheKey(int $courseId, int $userId): string
    {
        return self::CACHE_PREFIX_ENROLLMENT."course:{$courseId}:user:{$userId}";
    }

    protected function getActiveEnrollmentCacheKey(int $userId, int $courseId): string
    {
        return self::CACHE_PREFIX_ENROLLMENT."active:user:{$userId}:course:{$courseId}";
    }

    protected function getActiveEnrollmentDetailCacheKey(int $userId, int $courseId): string
    {
        return self::CACHE_PREFIX_ENROLLMENT."active_detail:user:{$userId}:course:{$courseId}";
    }

    protected function getRosterCacheKey(int $courseId, string $suffix = ''): string
    {
        $key = self::CACHE_PREFIX_ROSTER."course:{$courseId}";

        return $suffix ? "{$key}:{$suffix}" : $key;
    }

    public function invalidateEnrollmentCache(int $courseId, int $userId): void
    {
        Cache::tags(['enrollments', "course:{$courseId}"])
            ->forget($this->getEnrollmentCacheKey($courseId, $userId));

        Cache::tags(['enrollments', "course:{$courseId}"])
            ->forget($this->getActiveEnrollmentCacheKey($userId, $courseId));

        Cache::tags(['enrollments', "course:{$courseId}"])
            ->forget($this->getActiveEnrollmentDetailCacheKey($userId, $courseId));
    }

    public function invalidateRosterCache(int $courseId): void
    {
        Cache::tags(['roster', "course:{$courseId}"])->flush();
    }

    public function invalidateUserEnrollmentCaches(int $userId): void
    {
        $enrollments = $this->query()
            ->where('user_id', $userId)
            ->select(['id', 'course_id', 'user_id'])
            ->get();

        foreach ($enrollments as $enrollment) {
            $this->invalidateEnrollmentCache($enrollment->course_id, $userId);
        }
    }

    public function getStudentRoster(int $courseId): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->getRosterCacheKey($courseId, 'students');

        return Cache::tags(['roster', "course:{$courseId}"])
            ->remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($courseId) {
                return $this->query()
                    ->where('course_id', $courseId)
                    ->whereIn('status', [
                        \Modules\Enrollments\Enums\EnrollmentStatus::Active->value,
                        \Modules\Enrollments\Enums\EnrollmentStatus::Completed->value,
                    ])
                    ->with(['user:id,name,email'])
                    ->orderBy('enrolled_at', 'asc')
                    ->get();
            });
    }

    public function getEnrolledStudentIds(int $courseId): array
    {
        $cacheKey = $this->getRosterCacheKey($courseId, 'student_ids');

        return Cache::tags(['roster', "course:{$courseId}"])
            ->remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($courseId) {
                return $this->query()
                    ->where('course_id', $courseId)
                    ->whereIn('status', [
                        \Modules\Enrollments\Enums\EnrollmentStatus::Active->value,
                        \Modules\Enrollments\Enums\EnrollmentStatus::Completed->value,
                    ])
                    ->pluck('user_id')
                    ->toArray();
            });
    }
}
