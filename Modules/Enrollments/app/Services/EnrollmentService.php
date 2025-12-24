<?php

namespace Modules\Enrollments\Services;

use App\Contracts\EnrollmentKeyHasherInterface;
use App\Exceptions\BusinessException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\User;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Contracts\Services\EnrollmentServiceInterface;
use Modules\Enrollments\DTOs\CreateEnrollmentDTO;
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
use Spatie\QueryBuilder\QueryBuilder;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function __construct(
        private EnrollmentRepositoryInterface $repository,
        private EnrollmentKeyHasherInterface $keyHasher
    ) {}

    public function paginateByCourse(int $courseId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = QueryBuilder::for(Enrollment::class)
            ->where('course_id', $courseId)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts(['enrolled_at', 'completed_at', 'created_at'])
            ->defaultSort('-enrolled_at');

        return $query->paginate($perPage);
    }

    public function paginateByCourseIds(array $courseIds, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = QueryBuilder::for(Enrollment::class)
            ->whereIn('course_id', $courseIds)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('course_id'),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts(['enrolled_at', 'completed_at', 'created_at'])
            ->defaultSort('-enrolled_at');

        return $query->paginate($perPage);
    }

    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = QueryBuilder::for(Enrollment::class)
            ->where('user_id', $userId)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('course_id'),
                AllowedFilter::callback('enrolled_from', fn ($q, $value) => $q->whereDate('enrolled_at', '>=', $value)),
                AllowedFilter::callback('enrolled_to', fn ($q, $value) => $q->whereDate('enrolled_at', '<=', $value)),
            ])
            ->allowedIncludes(['user', 'course'])
            ->allowedSorts(['enrolled_at', 'completed_at', 'created_at'])
            ->defaultSort('-enrolled_at');

        return $query->paginate($perPage);
    }

    /**
     * Get managed enrollments for a user (courses they manage)
     */
    public function getManagedEnrollments(User $user, int $perPage = 15, ?string $courseSlug = null): array
    {
        // Get courses managed by user
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

        // Handle course_slug filter
        if ($courseSlug) {
            $course = $courses->firstWhere('slug', $courseSlug);
            if (! $course) {
                return [
                    'found' => false,
                    'paginator' => null,
                ];
            }
            $paginator = $this->paginateByCourse($course->id, $perPage);
        } else {
            $paginator = $this->paginateByCourseIds($courseIds, $perPage);
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

    /**
     * @return array{enrollment: Enrollment, status: string, message: string}
     *
     * @throws BusinessException
     */
    public function enroll(Course $course, User $user, CreateEnrollmentDTO $dto): array
    {
        $existing = $this->repository->findByCourseAndUser($course->id, $user->id);

        if ($existing && collect([EnrollmentStatus::Active, EnrollmentStatus::Pending])->contains($existing->status)) {
            throw new BusinessException('Anda sudah terdaftar pada course ini.', ['course' => 'Anda sudah terdaftar pada course ini.']);
        }

        $enrollment = $existing ?? new Enrollment([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        [$status, $message] = $this->determineStatusAndMessage($course, $dto);

        $enrollment->status = EnrollmentStatus::from($status);
        $enrollment->enrolled_at = $enrollment->enrolled_at ?? Carbon::now();

        if ($status !== EnrollmentStatus::Completed->value) {
            $enrollment->completed_at = null;
        }

        $enrollment->save();

        $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);

        EnrollmentCreated::dispatch($freshEnrollment);
        $this->sendEnrollmentEmails($freshEnrollment, $course, $user, $status);

        return [
            'enrollment' => $freshEnrollment,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * @throws BusinessException
     */
    public function cancel(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::Pending) {
            throw new BusinessException(
                'Hanya enrollment dengan status pending yang dapat dibatalkan.',
                ['enrollment' => 'Hanya enrollment dengan status pending yang dapat dibatalkan.']
            );
        }

        $enrollment->status = EnrollmentStatus::Cancelled;
        $enrollment->completed_at = null;
        $enrollment->save();

        return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
    }

    /**
     * @throws BusinessException
     */
    public function withdraw(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::Active) {
            throw new BusinessException(
                'Hanya enrollment aktif yang dapat mengundurkan diri.',
                ['enrollment' => 'Hanya enrollment aktif yang dapat mengundurkan diri.']
            );
        }

        $enrollment->status = EnrollmentStatus::Cancelled;
        $enrollment->completed_at = null;
        $enrollment->save();

        return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
    }

    /**
     * @throws BusinessException
     */
    public function approve(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::Pending) {
            throw new BusinessException(
                'Hanya permintaan enrollment pending yang dapat disetujui.',
                ['enrollment' => 'Hanya permintaan enrollment pending yang dapat disetujui.']
            );
        }

        $enrollment->status = EnrollmentStatus::Active;
        $enrollment->enrolled_at = Carbon::now();
        $enrollment->completed_at = null;
        $enrollment->save();

        $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);
        $course = $freshEnrollment->course;
        $student = $freshEnrollment->user;

        if ($student && $course) {
            $courseUrl = $this->getCourseUrl($course);
            Mail::to($student->email)->send(new StudentEnrollmentApprovedMail($student, $course, $courseUrl));
        }

        return $freshEnrollment;
    }

    /**
     * @throws BusinessException
     */
    public function decline(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::Pending) {
            throw new BusinessException(
                'Hanya permintaan enrollment pending yang dapat ditolak.',
                ['enrollment' => 'Hanya permintaan enrollment pending yang dapat ditolak.']
            );
        }

        $enrollment->status = EnrollmentStatus::Cancelled;
        $enrollment->completed_at = null;
        $enrollment->save();

        $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);
        $course = $freshEnrollment->course;
        $student = $freshEnrollment->user;

        if ($student && $course) {
            Mail::to($student->email)->send(new StudentEnrollmentDeclinedMail($student, $course));
        }

        return $freshEnrollment;
    }

    /**
     * @throws BusinessException
     */
    public function remove(Enrollment $enrollment): Enrollment
    {
        if (! collect([EnrollmentStatus::Active, EnrollmentStatus::Pending])->contains($enrollment->status)) {
            throw new BusinessException(
                'Hanya enrollment aktif atau pending yang dapat dikeluarkan.',
                ['enrollment' => 'Hanya enrollment aktif atau pending yang dapat dikeluarkan.']
            );
        }

        $enrollment->status = EnrollmentStatus::Cancelled;
        $enrollment->completed_at = null;
        $enrollment->save();

        return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function determineStatusAndMessage(Course $course, CreateEnrollmentDTO $dto): array
    {
        $type = $course->enrollment_type;

        $typeValue = $type instanceof \Modules\Schemes\Enums\EnrollmentType ? $type->value : ($type ?? 'auto_accept');

        return match ($typeValue) {
            'auto_accept' => ['active', 'Enrol berhasil. Anda sekarang terdaftar pada course ini.'],
            'key_based' => $this->handleKeyBasedEnrollment($course, $dto),
            'approval' => ['pending', 'Permintaan enrollment berhasil dikirim. Menunggu persetujuan.'],
            default => ['active', 'Enrol berhasil.'],
        };
    }

    /**
     * @return array{0: string, 1: string}
     *
     * @throws BusinessException
     */
    private function handleKeyBasedEnrollment(Course $course, CreateEnrollmentDTO $dto): array
    {
        $providedKey = trim((string) ($dto->enrollmentKey ?? ''));

        if ($providedKey === '') {
            throw new BusinessException('Kode enrollment wajib diisi.', ['enrollment_key' => 'Kode enrollment wajib diisi.']);
        }

        if (empty($course->enrollment_key_hash) || ! $this->keyHasher->verify($providedKey, $course->enrollment_key_hash)) {
            throw new BusinessException('Kode enrollment tidak valid.', ['enrollment_key' => 'Kode enrollment tidak valid.']);
        }

        return ['active', 'Enrol berhasil menggunakan kode kunci.'];
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

    /**
     * @return array<int, User>
     */
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
}
