<?php

namespace Modules\Enrollments\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
}
