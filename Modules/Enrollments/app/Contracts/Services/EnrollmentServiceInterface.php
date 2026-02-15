<?php

declare(strict_types=1);

namespace Modules\Enrollments\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

interface EnrollmentServiceInterface
{
    public function paginateByCourse(int $courseId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function paginateByCourseIds(array $courseIds, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function paginateByUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function paginateAll(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Enrollment;

    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment;

    public function cancel(Enrollment $enrollment): Enrollment;

    public function withdraw(Enrollment $enrollment): Enrollment;

    public function approve(Enrollment $enrollment): Enrollment;

    public function decline(Enrollment $enrollment): Enrollment;

    public function remove(Enrollment $enrollment): Enrollment;

    public function bulkApprove(array $enrollments): array;

    public function bulkDecline(array $enrollments): array;

    public function bulkRemove(array $enrollments): array;

    public function getEnrollmentsAuthorizedFor(User $user, array $enrollmentIds, string $ability): array;

    public function isUserEnrolledInCourse(int $userId, int $courseId): bool;

    public function getActiveEnrollment(int $userId, int $courseId): ?Enrollment;

    public function listEnrollments(User $user, int $perPage, array $filters = []): LengthAwarePaginator;

    public function listEnrollmentsForIndex(User $user, int $perPage, array $filters = []): LengthAwarePaginator;

    public function getManagedEnrollments(User $user, int $perPage, ?string $courseSlug, array $filters = []): array;

    public function findEnrollmentForAction(Course $course, User $user, array $data): Enrollment;

    public function getEnrollmentStatus(Course $course, User $user, array $data): array;

    public function enroll(User $user, Course $course, array $data): array;
}
