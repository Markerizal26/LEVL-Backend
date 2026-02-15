<?php

declare(strict_types=1);

namespace Modules\Enrollments\Services;

use App\Exceptions\BusinessException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Enrollments\Contracts\Services\EnrollmentServiceInterface;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Services\Support\EnrollmentFinder;
use Modules\Enrollments\Services\Support\EnrollmentLifecycleProcessor;
use Modules\Schemes\Models\Course;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function __construct(
        private readonly EnrollmentFinder $finder,
        private readonly EnrollmentLifecycleProcessor $lifecycleProcessor
    ) {}

    public function paginateByCourse(int $courseId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->paginateByCourse($courseId, $perPage, $filters);
    }

    public function paginateByCourseForIndex(int $courseId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->paginateByCourseForIndex($courseId, $perPage, $filters);
    }

    public function paginateByCourseIds(array $courseIds, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->paginateByCourseIds($courseIds, $perPage, $filters);
    }

    public function paginateByCourseIdsForIndex(array $courseIds, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->paginateByCourseIdsForIndex($courseIds, $perPage, $filters);
    }

    public function paginateByUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->paginateByUser($userId, $perPage, $filters);
    }

    public function paginateByUserForIndex(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->paginateByUserForIndex($userId, $perPage, $filters);
    }

    public function paginateAll(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->paginateAll($perPage, $filters);
    }

    public function paginateAllForIndex(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->paginateAllForIndex($perPage, $filters);
    }

    public function getManagedEnrollments(User $user, int $perPage = 15, ?string $courseSlug = null, array $filters = []): array
    {
        return $this->finder->getManagedEnrollments($user, $perPage, $courseSlug, $filters);
    }

    public function getManagedEnrollmentsForIndex(User $user, int $perPage = 15, ?string $courseSlug = null, array $filters = []): array
    {
        return $this->finder->getManagedEnrollmentsForIndex($user, $perPage, $courseSlug, $filters);
    }

    public function findById(int $id): ?Enrollment
    {
        return $this->finder->findById($id);
    }

    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment
    {
        return $this->finder->findByCourseAndUser($courseId, $userId);
    }

    public function isUserEnrolledInCourse(int $userId, int $courseId): bool
    {
        return $this->finder->isUserEnrolledInCourse($userId, $courseId);
    }

    public function getActiveEnrollment(int $userId, int $courseId): ?Enrollment
    {
        return $this->finder->getActiveEnrollment($userId, $courseId);
    }

    public function listEnrollments(User $user, int $perPage, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->listEnrollments($user, $perPage, $filters);
    }

    public function listEnrollmentsForIndex(User $user, int $perPage, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->listEnrollmentsForIndex($user, $perPage, $filters);
    }

    public function findEnrollmentForAction(Course $course, User $user, array $data): Enrollment
    {
        return $this->finder->findEnrollmentForAction($course, $user, $data);
    }

    public function getEnrollmentStatus(Course $course, User $user, array $data): array
    {
        return $this->finder->getEnrollmentStatus($course, $user, $data);
    }

    public function enroll(User $user, Course $course, array $data): array
    {
        $result = $this->lifecycleProcessor->enroll($user, $course, $data);
        cache()->tags(['enrollments'])->flush();

        return $result;
    }

    public function cancel(Enrollment $enrollment): Enrollment
    {
        $result = $this->lifecycleProcessor->cancel($enrollment);
        cache()->tags(['enrollments'])->flush();

        return $result;
    }

    public function withdraw(Enrollment $enrollment): Enrollment
    {
        $result = $this->lifecycleProcessor->withdraw($enrollment);
        cache()->tags(['enrollments'])->flush();

        return $result;
    }

    public function approve(Enrollment $enrollment): Enrollment
    {
        $result = $this->lifecycleProcessor->approve($enrollment);
        cache()->tags(['enrollments'])->flush();

        return $result;
    }

    public function decline(Enrollment $enrollment): Enrollment
    {
        $result = $this->lifecycleProcessor->decline($enrollment);
        cache()->tags(['enrollments'])->flush();

        return $result;
    }

    public function remove(Enrollment $enrollment): Enrollment
    {
        $result = $this->lifecycleProcessor->remove($enrollment);
        cache()->tags(['enrollments'])->flush();

        return $result;
    }

    public function bulkApprove(array $enrollments): array
    {
        return $this->bulkAction($enrollments, 'approve');
    }

    public function bulkDecline(array $enrollments): array
    {
        return $this->bulkAction($enrollments, 'decline');
    }

    public function bulkRemove(array $enrollments): array
    {
        return $this->bulkAction($enrollments, 'remove');
    }

    public function getEnrollmentsAuthorizedFor(User $user, array $enrollmentIds, string $ability): array
    {
        $enrollments = Enrollment::whereIn('id', $enrollmentIds)->get();

        return $enrollments->filter(fn (Enrollment $e) => Gate::forUser($user)->allows($ability, $e))->values()->all();
    }

    private function bulkAction(array $enrollments, string $action): array
    {
        $processed = [];
        $failed = [];

        foreach ($enrollments as $enrollment) {
            try {
                $updated = match ($action) {
                    'approve' => $this->lifecycleProcessor->approve($enrollment),
                    'decline' => $this->lifecycleProcessor->decline($enrollment),
                    'remove' => $this->lifecycleProcessor->remove($enrollment),
                    default => $enrollment,
                };
                $processed[] = $updated;
            } catch (BusinessException $e) {
                $failed[] = ['id' => $enrollment->id, 'reason' => $e->getMessage()];
            }
        }

        if (count($processed) > 0) {
            cache()->tags(['enrollments'])->flush();
        }

        return ['processed' => $processed, 'failed' => $failed];
    }
}
