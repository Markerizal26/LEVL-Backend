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
    /**
     * Cache TTL for student rosters (30 minutes).
     * Requirements: 28.10
     */
    protected const CACHE_TTL_ROSTER = 1800;

    /**
     * Cache key prefix for enrollment data.
     */
    protected const CACHE_PREFIX_ENROLLMENT = 'enrollment:';

    /**
     * Cache key prefix for roster data.
     */
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

    /**
     * Find enrollment by course and user with caching.
     * Requirements: 28.10
     */
    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment
    {
        $cacheKey = $this->getEnrollmentCacheKey($courseId, $userId);

        return Cache::remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($courseId, $userId) {
            return $this->query()
                ->withoutEagerLoads() // PENTING: Jangan load relasi
                ->where('course_id', $courseId)
                ->where('user_id', $userId)
                ->first();
        });
    }

    /**
     * Check if user has an active or completed enrollment with caching.
     * Requirements: 28.10
     */
    public function hasActiveEnrollment(int $userId, int $courseId): bool
    {
        $cacheKey = $this->getActiveEnrollmentCacheKey($userId, $courseId);

        return Cache::remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($userId, $courseId) {
            return $this->query()
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereIn('status', [\Modules\Enrollments\Enums\EnrollmentStatus::Active->value, \Modules\Enrollments\Enums\EnrollmentStatus::Completed->value])
                ->exists();
        });
    }

    /**
     * Get active or completed enrollment for user and course with caching.
     * Requirements: 28.10
     */
    public function getActiveEnrollment(int $userId, int $courseId): ?Enrollment
    {
        $cacheKey = $this->getActiveEnrollmentDetailCacheKey($userId, $courseId);

        return Cache::remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($userId, $courseId) {
            return $this->query()
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereIn('status', [\Modules\Enrollments\Enums\EnrollmentStatus::Active->value, \Modules\Enrollments\Enums\EnrollmentStatus::Completed->value])
                ->first();
        });
    }

    /**
     * Find active or completed enrollment by user and course ID with caching.
     * Requirements: 28.10
     */
    public function findActiveByUserAndCourse(int $userId, int $courseId): ?Enrollment
    {
        // Reuse the same cache as getActiveEnrollment
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

        // Invalidate enrollment cache after progress update
        $enrollment = Enrollment::find($enrollmentId);
        if ($enrollment) {
            $this->invalidateEnrollmentCache($enrollment->course_id, $enrollment->user_id);
        }
    }

    /**
     * Generate cache key for enrollment by course and user.
     * Requirements: 28.10
     */
    protected function getEnrollmentCacheKey(int $courseId, int $userId): string
    {
        return self::CACHE_PREFIX_ENROLLMENT."course:{$courseId}:user:{$userId}";
    }

    /**
     * Generate cache key for active enrollment check.
     * Requirements: 28.10
     */
    protected function getActiveEnrollmentCacheKey(int $userId, int $courseId): string
    {
        return self::CACHE_PREFIX_ENROLLMENT."active:user:{$userId}:course:{$courseId}";
    }

    /**
     * Generate cache key for active enrollment details.
     * Requirements: 28.10
     */
    protected function getActiveEnrollmentDetailCacheKey(int $userId, int $courseId): string
    {
        return self::CACHE_PREFIX_ENROLLMENT."active_detail:user:{$userId}:course:{$courseId}";
    }

    /**
     * Generate cache key for course roster.
     * Requirements: 28.10
     */
    protected function getRosterCacheKey(int $courseId, string $suffix = ''): string
    {
        $key = self::CACHE_PREFIX_ROSTER."course:{$courseId}";

        return $suffix ? "{$key}:{$suffix}" : $key;
    }

    /**
     * Invalidate enrollment cache for a specific user and course.
     * Requirements: 28.10
     */
    public function invalidateEnrollmentCache(int $courseId, int $userId): void
    {
        Cache::forget($this->getEnrollmentCacheKey($courseId, $userId));
        Cache::forget($this->getActiveEnrollmentCacheKey($userId, $courseId));
        Cache::forget($this->getActiveEnrollmentDetailCacheKey($userId, $courseId));
    }

    /**
     * Invalidate roster cache for a course.
     * Requirements: 28.10
     */
    public function invalidateRosterCache(int $courseId): void
    {
        Cache::forget($this->getRosterCacheKey($courseId));
        Cache::forget($this->getRosterCacheKey($courseId, 'students'));
        Cache::forget($this->getRosterCacheKey($courseId, 'student_ids'));
        // Note: For production with Redis, use cache tags for more efficient invalidation
    }

    /**
     * Invalidate all caches for a user's enrollments.
     * Useful when user data changes.
     * Requirements: 28.10
     */
    public function invalidateUserEnrollmentCaches(int $userId): void
    {
        // Get all enrollments for the user and invalidate their caches
        $enrollments = $this->query()
            ->where('user_id', $userId)
            ->select(['id', 'course_id', 'user_id'])
            ->get();

        foreach ($enrollments as $enrollment) {
            $this->invalidateEnrollmentCache($enrollment->course_id, $userId);
        }
    }

    /**
     * Get the student roster (all active enrollments) for a course with caching.
     * Returns a collection of active enrollments with user data.
     * Requirements: 28.10
     *
     * @param  int  $courseId  The course ID
     * @return \Illuminate\Database\Eloquent\Collection<int, Enrollment>
     */
    public function getStudentRoster(int $courseId): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->getRosterCacheKey($courseId, 'students');

        return Cache::remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($courseId) {
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

    /**
     * Get student IDs enrolled in a course with caching.
     * Useful for quick lookups without loading full enrollment data.
     * Requirements: 28.10
     *
     * @param  int  $courseId  The course ID
     * @return array<int>
     */
    public function getEnrolledStudentIds(int $courseId): array
    {
        $cacheKey = $this->getRosterCacheKey($courseId, 'student_ids');

        return Cache::remember($cacheKey, self::CACHE_TTL_ROSTER, function () use ($courseId) {
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
