<?php

declare(strict_types=1);

namespace Modules\Enrollments\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Enrollments\Models\Enrollment;

interface EnrollmentRepositoryInterface
{
    /**
     * Paginate enrollments by course ID.
     * Custom QueryFilter reads filter/sort from params or request.
     */
    public function paginateByCourse(int $courseId, array $params = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginate enrollments by multiple course IDs.
     * Custom QueryFilter reads filter/sort from params or request.
     */
    public function paginateByCourseIds(array $courseIds, array $params = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginate enrollments by user ID.
     * Custom QueryFilter reads filter/sort from params or request.
     */
    public function paginateByUser(int $userId, array $params = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find enrollment by course and user.
     */
    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment;

    /**
     * Check if user has an active or completed enrollment.
     */
    public function hasActiveEnrollment(int $userId, int $courseId): bool;

    /**
     * Get active or completed enrollment for user and course.
     */
    public function getActiveEnrollment(int $userId, int $courseId): ?Enrollment;

    /**
     * Find active or completed enrollment by user and course ID.
     */
    public function findActiveByUserAndCourse(int $userId, int $courseId): ?Enrollment;

    /**
     * Increment lesson progress attempt count or create new progress.
     */
    public function incrementLessonProgress(int $enrollmentId, int $lessonId): void;

    /**
     * Get the student roster (all active enrollments) for a course with caching.
     * Returns a collection of active enrollments with user data.
     * Requirements: 28.10
     *
     * @param  int  $courseId  The course ID
     * @return Collection<int, Enrollment>
     */
    public function getStudentRoster(int $courseId): Collection;

    /**
     * Get student IDs enrolled in a course with caching.
     * Useful for quick lookups without loading full enrollment data.
     * Requirements: 28.10
     *
     * @param  int  $courseId  The course ID
     * @return array<int>
     */
    public function getEnrolledStudentIds(int $courseId): array;

    /**
     * Invalidate enrollment cache for a specific user and course.
     * Requirements: 28.10
     */
    public function invalidateEnrollmentCache(int $courseId, int $userId): void;

    /**
     * Invalidate roster cache for a course.
     * Requirements: 28.10
     */
    public function invalidateRosterCache(int $courseId): void;

    /**
     * Get course progress percent with caching.
     * Requirements: 28.10
     */
    public function getCourseProgress(int $enrollmentId): float;
}
