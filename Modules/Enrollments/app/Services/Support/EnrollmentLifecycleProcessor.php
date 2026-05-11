<?php

declare(strict_types=1);

namespace Modules\Enrollments\Services\Support;

use App\Contracts\EnrollmentKeyHasherInterface;
use App\Exceptions\BusinessException;
use App\Support\Helpers\UrlHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Models\Enrollment;
use Modules\Mail\Mail\Enrollments\AdminEnrollmentNotificationMail;
use Modules\Mail\Mail\Enrollments\StudentEnrollmentActiveMail;
use Modules\Mail\Mail\Enrollments\StudentEnrollmentApprovedMail;
use Modules\Mail\Mail\Enrollments\StudentEnrollmentDeclinedMail;
use Modules\Mail\Mail\Enrollments\StudentEnrollmentManualActiveMail;
use Modules\Mail\Mail\Enrollments\StudentEnrollmentManualPendingMail;
use Modules\Mail\Mail\Enrollments\StudentEnrollmentPendingMail;
use Modules\Mail\Mail\Enrollments\StudentEnrollmentScheduledMail;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Models\Course;

class EnrollmentLifecycleProcessor
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $repository,
        private readonly EnrollmentKeyHasherInterface $keyHasher
    ) {}

    public function enroll(User $user, Course $course, array $data): array
    {
        $this->validateUserAndCourse($user, $course);

        $enrollmentKey = $data['enrollment_key'] ?? null;

        if ($course->enrollment_type === EnrollmentType::KeyBased) {
            $this->validateEnrollmentKey($enrollmentKey, $course);
        }

        $existingEnrollment = $this->repository->findByCourseAndUser($course->id, $user->id);

        if ($existingEnrollment) {
            if ($existingEnrollment->status === EnrollmentStatus::Active) {
                throw new BusinessException(__('messages.enrollments.already_enrolled'));
            }
            if ($existingEnrollment->status === EnrollmentStatus::Pending) {
                throw new BusinessException(__('messages.enrollments.enrollment_pending'));
            }
        }

        return DB::transaction(function () use ($user, $course, $existingEnrollment) {
            $initialStatus = $this->determineInitialStatus($course->enrollment_type);
            $enrolledAt = Carbon::now();

            $enrollment = $this->saveEnrollment($existingEnrollment, $user->id, $course->id, $initialStatus, $enrolledAt, false);

            if (! $existingEnrollment) {
                \Modules\Enrollments\Events\EnrollmentCreated::dispatch($enrollment);
            }

            $this->sendEnrollmentEmails($enrollment, $course, $user, $initialStatus);

            return [
                'status' => 'success',
                'enrollment' => $enrollment,
                'message' => $initialStatus === EnrollmentStatus::Pending
                    ? __('messages.enrollments.enrollment_pending')
                    : __('messages.enrollments.enrolled_successfully'),
            ];
        });
    }

    public function enrollManually(User $actor, Course $course, array $data): array
    {
        $studentId = (int) $data['student_id'];
        $student = User::findOrFail($studentId);
        $requestedStatus = EnrollmentStatus::from($data['initial_status']);

        if ($requestedStatus === EnrollmentStatus::Pending) {
            $enrollmentDate = Carbon::now();
            $autoActivateOnEnrolledAt = false;
            $initialStatus = EnrollmentStatus::Pending;
        } else {
            $enrollmentDate = isset($data['enrollment_date']) ? Carbon::parse($data['enrollment_date']) : Carbon::now();
            $autoActivateOnEnrolledAt = $enrollmentDate->isFuture();
            $initialStatus = $autoActivateOnEnrolledAt ? EnrollmentStatus::Pending : EnrollmentStatus::Active;
        }

        $notifyStudent = (bool) ($data['is_notify_student'] ?? false);

        $existingEnrollment = $this->repository->findByCourseAndUser($course->id, $student->id);

        if ($existingEnrollment && $existingEnrollment->status === EnrollmentStatus::Active) {
            throw new BusinessException(__('messages.enrollments.already_enrolled'));
        }

        return DB::transaction(function () use ($student, $course, $existingEnrollment, $initialStatus, $enrollmentDate, $autoActivateOnEnrolledAt, $notifyStudent) {
            $enrollment = $this->saveEnrollment(
                $existingEnrollment,
                $student->id,
                $course->id,
                $initialStatus,
                $enrollmentDate,
                $autoActivateOnEnrolledAt
            );
            $isNew = ! $existingEnrollment;

            if ($isNew) {
                \Modules\Enrollments\Events\EnrollmentCreated::dispatch($enrollment);
            }

            if ($notifyStudent) {
                $this->sendManualEnrollmentEmail($enrollment, $course, $student, $enrollmentDate);
            }

            $message = $autoActivateOnEnrolledAt
                ? __('messages.enrollments.scheduled_successfully', ['date' => $enrollmentDate->format('d M Y')])
                : __('messages.enrollments.enrolled_successfully');

            return [
                'status' => 'success',
                'enrollment' => $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email', 'user.media']),
                'message' => $message,
                'is_scheduled' => $enrollmentDate->isFuture(),
            ];
        });
    }

    public function cancel(Enrollment $enrollment): Enrollment
    {
        return $this->updateEnrollmentStatus($enrollment, EnrollmentStatus::Cancelled, EnrollmentStatus::Pending, __('messages.enrollments.cannot_cancel_pending'));
    }

    public function withdraw(Enrollment $enrollment): Enrollment
    {
        return $this->updateEnrollmentStatus($enrollment, EnrollmentStatus::Cancelled, EnrollmentStatus::Active, __('messages.enrollments.cannot_withdraw_active'));
    }

    public function approve(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment): Enrollment {
            $enrollment = $this->updateEnrollmentStatus($enrollment, EnrollmentStatus::Active, EnrollmentStatus::Pending, __('messages.enrollments.cannot_approve_pending'));
            $course = $enrollment->course;
            $student = $enrollment->user;

            if ($student && $course) {
                $courseUrl = $this->getCourseUrl($course);
                Mail::to($student->email)
                    ->queue((new StudentEnrollmentApprovedMail($student, $course, $courseUrl))->onQueue('emails-transactional'));
            }

            return $enrollment;
        });
    }

    public function decline(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment): Enrollment {
            $enrollment = $this->updateEnrollmentStatus($enrollment, EnrollmentStatus::Cancelled, EnrollmentStatus::Pending, __('messages.enrollments.cannot_decline_pending'));
            $course = $enrollment->course;
            $student = $enrollment->user;

            if ($student && $course) {
                Mail::to($student->email)
                    ->queue((new StudentEnrollmentDeclinedMail($student, $course))->onQueue('emails-transactional'));
            }

            return $enrollment;
        });
    }

    public function remove(Enrollment $enrollment): Enrollment
    {
        $allowed = [EnrollmentStatus::Active, EnrollmentStatus::Pending];
        if (! in_array($enrollment->status, $allowed)) {
            throw new BusinessException(__('messages.enrollments.cannot_remove_active_pending'));
        }

        return DB::transaction(function () use ($enrollment): Enrollment {
            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->completed_at = null;
            $enrollment->save();

            return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email', 'user.media']);
        });
    }

    

    private function validateUserAndCourse(User $user, Course $course): void
    {
        if ($course->status !== CourseStatus::Published) {
            throw new BusinessException(__('messages.enrollments.course_not_published'));
        }

        $invalidStatuses = [UserStatus::Pending, UserStatus::Inactive, UserStatus::Banned];
        if (in_array($user->status, $invalidStatuses)) {
            throw new BusinessException(__('messages.enrollments.user_status_'.$user->status->value));
        }
    }

    private function validateEnrollmentKey(?string $key, Course $course): void
    {
        if (empty($key)) {
            throw new BusinessException(__('messages.enrollments.key_required'));
        }

        $isValid = false;
        if (! empty($course->enrollment_key_encrypted)) {
            $encrypter = app(\App\Contracts\EnrollmentKeyEncrypterInterface::class);
            $isValid = $encrypter->verify($key, $course->enrollment_key_encrypted);
        }

        if (! $isValid && ! empty($course->enrollment_key_hash)) {
            $isValid = $this->keyHasher->verify($key, $course->enrollment_key_hash);
        }

        if (! $isValid) {
            throw new BusinessException(__('messages.enrollments.key_invalid'));
        }
    }

    private function determineInitialStatus(EnrollmentType|string $enrollmentType): EnrollmentStatus
    {
        $resolvedType = $enrollmentType instanceof EnrollmentType
            ? $enrollmentType
            : EnrollmentType::tryFrom($enrollmentType);

        return match ($resolvedType) {
            EnrollmentType::AutoAccept, EnrollmentType::KeyBased => EnrollmentStatus::Active,
            EnrollmentType::Approval => EnrollmentStatus::Pending,
            default => EnrollmentStatus::Pending,
        };
    }

    private function saveEnrollment(?Enrollment $existing, int $userId, int $courseId, EnrollmentStatus $status, Carbon $enrolledAt, bool $autoActivateOnEnrolledAt): Enrollment
    {
        if ($existing) {
            $existing->status = $status;
            $existing->enrolled_at = $enrolledAt;
            $existing->auto_activate_on_enrolled_at = $autoActivateOnEnrolledAt;
            $existing->completed_at = null;
            $existing->save();

            return $existing;
        }

        $enrollment = new Enrollment;
        $enrollment->user_id = $userId;
        $enrollment->course_id = $courseId;
        $enrollment->status = $status;
        $enrollment->enrolled_at = $enrolledAt;
        $enrollment->auto_activate_on_enrolled_at = $autoActivateOnEnrolledAt;
        $enrollment->save();

        return $enrollment;
    }

    private function sendEnrollmentEmails(Enrollment $enrollment, Course $course, User $student, EnrollmentStatus $status): void
    {
        $courseUrl = $this->getCourseUrl($course);

        $mailClass = match ($status) {
            EnrollmentStatus::Active => StudentEnrollmentActiveMail::class,
            EnrollmentStatus::Pending => StudentEnrollmentPendingMail::class,
            default => null,
        };

        if ($mailClass) {
            Mail::to($student->email)
                ->queue((new $mailClass($student, $course, $courseUrl))->onQueue('emails-transactional'));
        }

        $this->notifyCourseManagers($enrollment, $course, $student);
    }

    private function sendManualEnrollmentEmail(Enrollment $enrollment, Course $course, User $student, Carbon $enrollmentDate): void
    {
        $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);
        $courseUrl = $this->getCourseUrl($course);

        if ($enrollmentDate->isFuture()) {
            Mail::to($student->email)
                ->queue((new StudentEnrollmentScheduledMail($student, $course, $enrollmentDate, $courseUrl))->onQueue('emails-transactional'));
        } elseif ($freshEnrollment->status === EnrollmentStatus::Active) {
            Mail::to($student->email)
                ->queue((new StudentEnrollmentManualActiveMail($student, $course, $courseUrl))->onQueue('emails-transactional'));
        } else {
            Mail::to($student->email)
                ->queue((new StudentEnrollmentManualPendingMail($student, $course, $courseUrl))->onQueue('emails-transactional'));
        }
    }

    private function notifyCourseManagers(Enrollment $enrollment, Course $course, User $student): void
    {
        $managers = $this->getCourseManagers($course);
        $enrollmentsUrl = $this->getEnrollmentsUrl($course);

        foreach ($managers as $manager) {
            if ($manager && $manager->email) {
                Mail::to($manager->email)
                    ->queue((new AdminEnrollmentNotificationMail($manager, $student, $course, $enrollment, $enrollmentsUrl))->onQueue('emails-transactional'));
            }
        }
    }

    private function getCourseManagers(Course $course): array
    {
        $freshCourse = $course->fresh(['instructor', 'instructors']);

        return collect([$freshCourse?->instructor, ...($freshCourse?->instructors?->all() ?? [])])
            ->filter()
            ->unique('id')
            ->all();
    }

    private function getCourseUrl(Course $course): string
    {
        return UrlHelper::getCourseUrl($course);
    }

    private function getEnrollmentsUrl(Course $course): string
    {
        return UrlHelper::getEnrollmentsUrl($course);
    }

    private function updateEnrollmentStatus(Enrollment $enrollment, EnrollmentStatus $newStatus, EnrollmentStatus $requiredStatus, string $errorMessage): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $newStatus, $requiredStatus, $errorMessage) {
            if ($enrollment->status !== $requiredStatus) {
                throw new BusinessException($errorMessage);
            }

            $enrollment->status = $newStatus;
            $enrollment->completed_at = null;

            if ($newStatus === EnrollmentStatus::Active) {
                $enrollment->enrolled_at = Carbon::now();
            }

            $enrollment->save();

            return $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email', 'user.media']);
        });
    }
}