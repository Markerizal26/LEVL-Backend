<?php

declare(strict_types=1);

namespace Modules\Enrollments\Services;

use App\Contracts\EnrollmentKeyHasherInterface;
use App\Exceptions\BusinessException;
use App\Support\Helpers\UrlHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\User;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Contracts\Services\EnrollmentServiceInterface;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Events\EnrollmentCreated;
use Modules\Enrollments\Mail\AdminEnrollmentNotificationMail;
use Modules\Enrollments\Mail\StudentEnrollmentActiveMail;
use Modules\Enrollments\Mail\StudentEnrollmentApprovedMail;
use Modules\Enrollments\Mail\StudentEnrollmentDeclinedMail;
use Modules\Enrollments\Mail\StudentEnrollmentPendingMail;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $repository,
        private readonly EnrollmentKeyHasherInterface $keyHasher
    ) {}

    public function paginateByCourse(int $courseId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = $filters['search'] ?? null;

        $builder = QueryBuilder::for(Enrollment::class)
            ->with(['user', 'course'])
            ->where('course_id', $courseId);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        $prioritySort = AllowedSort::callback('priority', function ($query, $descending) {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                ->orderBy('created_at', 'desc');
        });

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $prioritySort,
            ])
            ->defaultSort($prioritySort)
            ->paginate($perPage);
    }

    public function paginateByCourseForIndex(int $courseId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = $filters['search'] ?? null;

        $builder = QueryBuilder::for(Enrollment::class)
            ->with(['user:id,name,email', 'course:id,title,slug,code'])
            ->where('course_id', $courseId);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        $prioritySort = AllowedSort::callback('priority', function ($query, $descending) {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                ->orderBy('created_at', 'desc');
        });

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $prioritySort,
            ])
            ->defaultSort($prioritySort)
            ->paginate($perPage);
    }

    public function paginateByCourseIds(array $courseIds, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = $filters['search'] ?? null;

        $builder = QueryBuilder::for(Enrollment::class)
            ->with(['user', 'course'])
            ->whereIn('course_id', $courseIds);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        $prioritySort = AllowedSort::callback('priority', function ($query, $descending) {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                ->orderBy('created_at', 'desc');
        });

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('course_slug', function ($query, $value) {
                    $course = \Modules\Schemes\Models\Course::where('slug', $value)->first();
                    if ($course) {
                        $query->where('course_id', $course->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $prioritySort,
            ])
            ->defaultSort($prioritySort)
            ->paginate($perPage);
    }

    public function paginateByCourseIdsForIndex(array $courseIds, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = $filters['search'] ?? null;

        $builder = QueryBuilder::for(Enrollment::class)
            ->with(['user:id,name,email', 'course:id,title,slug,code'])
            ->whereIn('course_id', $courseIds);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        $prioritySort = AllowedSort::callback('priority', function ($query, $descending) {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                ->orderBy('created_at', 'desc');
        });

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('course_slug', function ($query, $value) {
                    $course = \Modules\Schemes\Models\Course::where('slug', $value)->first();
                    if ($course) {
                        $query->where('course_id', $course->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $prioritySort,
            ])
            ->defaultSort($prioritySort)
            ->paginate($perPage);
    }

    public function paginateByUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = $filters['search'] ?? null;

        $builder = QueryBuilder::for(Enrollment::class)
            ->with(['user', 'course'])
            ->where('user_id', $userId);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('course_slug', function ($query, $value) {
                    $course = \Modules\Schemes\Models\Course::where('slug', $value)->first();
                    if ($course) {
                        $query->where('course_id', $course->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts(['enrolled_at', 'completed_at', 'created_at'])
            ->defaultSort('-enrolled_at')
            ->paginate($perPage);
    }

    public function paginateByUserForIndex(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = $filters['search'] ?? null;

        $builder = QueryBuilder::for(Enrollment::class)
            ->with(['user:id,name,email', 'course:id,title,slug,code'])
            ->where('user_id', $userId);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('course_slug', function ($query, $value) {
                    $course = \Modules\Schemes\Models\Course::where('slug', $value)->first();
                    if ($course) {
                        $query->where('course_id', $course->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedSorts(['enrolled_at', 'completed_at', 'created_at'])
            ->defaultSort('-enrolled_at')
            ->paginate($perPage);
    }

    public function paginateAll(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = $filters['search'] ?? null;

        $builder = QueryBuilder::for(Enrollment::class)
            ->with(['user', 'course']);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        $prioritySort = AllowedSort::callback('priority', function ($query, $descending) {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                ->orderBy('created_at', 'desc');
        });

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('course_slug', function ($query, $value) {
                    $course = \Modules\Schemes\Models\Course::where('slug', $value)->first();
                    if ($course) {
                        $query->where('course_id', $course->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $prioritySort,
            ])
            ->defaultSort($prioritySort)
            ->paginate($perPage);
    }

    public function paginateAllForIndex(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $searchQuery = $filters['search'] ?? null;

        $builder = QueryBuilder::for(Enrollment::class)
            ->with(['user:id,name,email', 'course:id,title,slug,code']);

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Enrollment::search($searchQuery)->take(1000)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        $prioritySort = AllowedSort::callback('priority', function ($query, $descending) {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
                ->orderBy('created_at', 'desc');
        });

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('course_slug', function ($query, $value) {
                    $course = \Modules\Schemes\Models\Course::where('slug', $value)->first();
                    if ($course) {
                        $query->where('course_id', $course->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedSorts([
                'enrolled_at',
                'completed_at',
                'created_at',
                $prioritySort,
            ])
            ->defaultSort($prioritySort)
            ->paginate($perPage);
    }

    public function getManagedEnrollments(User $user, int $perPage = 15, ?string $courseSlug = null, array $filters = []): array
    {
        $courses = Course::query()
            ->select(['id', 'slug', 'title'])
            ->where(function ($query) use ($user) {
                $query
                    ->where('instructor_id', $user->id)
                    ->orWhereHas('admins', function ($adminQuery) use ($user) {
                        $adminQuery->where('user_id', $user->id);
                    });
            })
            ->get();

        $courseIds = $courses->pluck('id')->all();

        if ($courseSlug) {
            $course = $courses->firstWhere('slug', $courseSlug);
            if (! $course) {
                return [
                    'found' => false,
                    'paginator' => null,
                ];
            }
            $paginator = $this->paginateByCourse($course->id, $perPage, $filters);
        } else {
            $paginator = $this->paginateByCourseIds($courseIds, $perPage, $filters);
        }

        return [
            'found' => true,
            'paginator' => $paginator,
        ];
    }

    public function getManagedEnrollmentsForIndex(User $user, int $perPage = 15, ?string $courseSlug = null, array $filters = []): array
    {
        $courses = Course::query()
            ->select(['id', 'slug', 'title'])
            ->where(function ($query) use ($user) {
                $query
                    ->where('instructor_id', $user->id)
                    ->orWhereHas('admins', function ($adminQuery) use ($user) {
                        $adminQuery->where('user_id', $user->id);
                    });
            })
            ->get();

        $courseIds = $courses->pluck('id')->all();

        if ($courseSlug) {
            $course = $courses->firstWhere('slug', $courseSlug);
            if (! $course) {
                return [
                    'found' => false,
                    'paginator' => null,
                ];
            }
            $paginator = $this->paginateByCourseForIndex($course->id, $perPage, $filters);
        } else {
            $paginator = $this->paginateByCourseIdsForIndex($courseIds, $perPage, $filters);
        }

        return [
            'found' => true,
            'paginator' => $paginator,
        ];
    }

    public function findById(int $id): ?Enrollment
    {
        return $this->repository->findById($id);
    }

    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment
    {
        return $this->repository->findByCourseAndUser($courseId, $userId);
    }

    public function cancel(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Pending) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_cancel_pending'),
                    ['enrollment' => __('messages.enrollments.cannot_cancel_pending')]
                );
            }

            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->completed_at = null;

            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            $this->invalidateEnrollmentCache($enrollment);

            return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
        });
    }

    public function withdraw(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Active) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_withdraw_active'),
                    ['enrollment' => __('messages.enrollments.cannot_withdraw_active')]
                );
            }

            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->completed_at = null;

            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            $this->invalidateEnrollmentCache($enrollment);

            return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
        });
    }

    public function approve(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Pending) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_approve_pending'),
                    ['enrollment' => __('messages.enrollments.cannot_approve_pending')]
                );
            }

            $enrollment->status = EnrollmentStatus::Active;
            $enrollment->enrolled_at = Carbon::now();
            $enrollment->completed_at = null;

            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            $this->invalidateEnrollmentCache($enrollment);

            $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);
            $course = $freshEnrollment->course;
            $student = $freshEnrollment->user;

            if ($student && $course) {
                $courseUrl = $this->getCourseUrl($course);
                Mail::to($student->email)->send(new StudentEnrollmentApprovedMail($student, $course, $courseUrl));
            }

            return $freshEnrollment;
        });
    }

    public function decline(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Pending) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_decline_pending'),
                    ['enrollment' => __('messages.enrollments.cannot_decline_pending')]
                );
            }

            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->completed_at = null;

            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            $this->invalidateEnrollmentCache($enrollment);

            $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);
            $course = $freshEnrollment->course;
            $student = $freshEnrollment->user;

            if ($student && $course) {
                Mail::to($student->email)->send(new StudentEnrollmentDeclinedMail($student, $course));
            }

            return $freshEnrollment;
        });
    }

    public function remove(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if (! collect([EnrollmentStatus::Active, EnrollmentStatus::Pending])->contains($enrollment->status)) {
                throw new BusinessException(
                    __('messages.enrollments.cannot_remove_active_pending'),
                    ['enrollment' => __('messages.enrollments.cannot_remove_active_pending')]
                );
            }

            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->completed_at = null;

            Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());

            $this->invalidateEnrollmentCache($enrollment);

            return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
        });
    }

    private function sendEnrollmentEmails(Enrollment $enrollment, Course $course, User $student, string $status): void
    {
        if ($status === EnrollmentStatus::Active->value) {
            $courseUrl = $this->getCourseUrl($course);
            Mail::to($student->email)->send(new StudentEnrollmentActiveMail($student, $course, $courseUrl));
        } elseif ($status === EnrollmentStatus::Pending->value) {
            Mail::to($student->email)->send(new StudentEnrollmentPendingMail($student, $course));
        }

        $this->notifyCourseManagers($enrollment, $course, $student);
    }

    private function notifyCourseManagers(Enrollment $enrollment, Course $course, User $student): void
    {
        $managers = $this->getCourseManagers($course);
        $enrollmentsUrl = $this->getEnrollmentsUrl($course);

        foreach ($managers as $manager) {
            if ($manager && $manager->email) {
                Mail::to($manager->email)->send(
                    new AdminEnrollmentNotificationMail($manager, $student, $course, $enrollment, $enrollmentsUrl)
                );
            }
        }
    }

    private function getCourseManagers(Course $course): array
    {
        $managers = [];
        $managerIds = [];

        $course = $course->fresh(['instructor', 'admins']);

        if ($course->instructor_id && $course->instructor) {
            $instructor = $course->instructor;
            $managers[] = $instructor;
            $managerIds[] = $instructor->id;
        }

        foreach ($course->admins as $admin) {
            if ($admin && ! collect($managerIds)->contains($admin->id)) {
                $managers[] = $admin;
                $managerIds[] = $admin->id;
            }
        }

        return $managers;
    }

    private function getCourseUrl(Course $course): string
    {
        return UrlHelper::getCourseUrl($course);
    }

    private function getEnrollmentsUrl(Course $course): string
    {
        return UrlHelper::getEnrollmentsUrl($course);
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

        if ($user->hasRole('Admin') || $user->hasRole('Instructor')) {
            $result = $this->getManagedEnrollments($user, $perPage, $courseSlug, $filters);

            if (! $result['found']) {
                throw new BusinessException(
                    __('messages.enrollments.course_not_managed'),
                    []
                );
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

        if ($user->hasRole('Admin') || $user->hasRole('Instructor')) {
            $result = $this->getManagedEnrollmentsForIndex($user, $perPage, $courseSlug, $filters);

            if (! $result['found']) {
                throw new BusinessException(
                    __('messages.enrollments.course_not_managed'),
                    []
                );
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
            throw new BusinessException(
                __('messages.enrollments.not_found'),
                []
            );
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

    public function enroll(User $user, Course $course, array $data): array
    {
        $enrollmentKey = $data['enrollment_key'] ?? null;

        if ($course->status !== \Modules\Schemes\Enums\CourseStatus::Published) {
            throw new BusinessException(
                __('messages.enrollments.course_not_published'),
                ['course' => __('messages.enrollments.course_not_published')]
            );
        }

        if ($course->enrollment_type === \Modules\Schemes\Enums\EnrollmentType::KeyBased) {
            if (empty($enrollmentKey)) {
                throw new BusinessException(
                    __('messages.enrollments.key_required'),
                    ['enrollment_key' => __('messages.enrollments.key_required')]
                );
            }

            if (! $this->keyHasher->check($enrollmentKey, $course->enrollment_key_hash)) {
                throw new BusinessException(
                    __('messages.enrollments.invalid_key'),
                    ['enrollment_key' => __('messages.enrollments.invalid_key')]
                );
            }
        }

        $existingEnrollment = $this->repository->findByCourseAndUser($course->id, $user->id);

        if ($existingEnrollment) {
            if ($existingEnrollment->status === EnrollmentStatus::Active) {
                throw new BusinessException(
                    __('messages.enrollments.already_enrolled'),
                    []
                );
            }
            if ($existingEnrollment->status === EnrollmentStatus::Pending) {
                throw new BusinessException(
                    __('messages.enrollments.enrollment_pending'),
                    []
                );
            }

        }

        return DB::transaction(function () use ($user, $course, $existingEnrollment) {

            $initialStatus = match ($course->enrollment_type) {
                \Modules\Schemes\Enums\EnrollmentType::AutoAccept,
                \Modules\Schemes\Enums\EnrollmentType::KeyBased => EnrollmentStatus::Active,
                \Modules\Schemes\Enums\EnrollmentType::Approval => EnrollmentStatus::Pending,
                default => EnrollmentStatus::Pending,
            };

            $enrolledAt = Carbon::now();

            if ($existingEnrollment) {
                $existingEnrollment->status = $initialStatus;
                $existingEnrollment->enrolled_at = $enrolledAt;
                $existingEnrollment->completed_at = null;

                Enrollment::withoutSyncingToSearch(fn () => $existingEnrollment->save());
                $enrollment = $existingEnrollment;
            } else {
                $enrollment = new Enrollment;
                $enrollment->user_id = $user->id;
                $enrollment->course_id = $course->id;
                $enrollment->status = $initialStatus;
                $enrollment->enrolled_at = $enrolledAt;

                Enrollment::withoutSyncingToSearch(fn () => $enrollment->save());
            }

            $this->invalidateEnrollmentCache($enrollment);

            event(new EnrollmentCreated($enrollment));

            $freshEnrollment = $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);

            $message = $initialStatus === EnrollmentStatus::Pending
                ? __('messages.enrollments.approval_sent')
                : __('messages.enrollments.auto_accept_success');

            return [
                'enrollment' => $freshEnrollment,
                'message' => $message,
            ];
        });
    }

    /**
     * Invalidate enrollment cache for a specific enrollment.
     * Requirements: 28.10
     */
    private function invalidateEnrollmentCache(Enrollment $enrollment): void
    {
        if ($this->repository instanceof \Modules\Enrollments\Repositories\EnrollmentRepository) {
            $this->repository->invalidateEnrollmentCache($enrollment->course_id, $enrollment->user_id);
            $this->repository->invalidateRosterCache($enrollment->course_id);
        }
    }
}
