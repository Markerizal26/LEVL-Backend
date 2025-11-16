<?php

namespace Modules\Enrollments\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Enrollments\Events\EnrollmentCreated;
use Modules\Enrollments\Mail\AdminEnrollmentNotificationMail;
use Modules\Enrollments\Mail\StudentEnrollmentActiveMail;
use Modules\Enrollments\Mail\StudentEnrollmentApprovedMail;
use Modules\Enrollments\Mail\StudentEnrollmentDeclinedMail;
use Modules\Enrollments\Mail\StudentEnrollmentPendingMail;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;
use Modules\Auth\Models\User;

class EnrollmentService
{
    /**
     * @param  \Modules\Schemes\Models\Course  $course
     * @param  \Modules\Auth\Models\User  $user
     * @param  array<string, mixed>  $payload
     * @return array{enrollment: Enrollment, status: string, message: string}
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function enroll(Course $course, User $user, array $payload = []): array
    {
        $existing = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing && in_array($existing->status, ['active', 'pending'], true)) {
            throw ValidationException::withMessages([
                'course' => 'Anda sudah terdaftar pada course ini.',
            ]);
        }

        $enrollment = $existing ?? new Enrollment([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        [$status, $message] = $this->determineStatusAndMessage($course, $payload);

        $enrollment->status = $status;
        // Progress is now stored in course_progress table, not in enrollments
        // enrolled_at cannot be null per migration, always set to now
        $enrollment->enrolled_at = $enrollment->enrolled_at ?? Carbon::now();

        if ($status !== 'completed') {
            $enrollment->completed_at = null;
        }

        $enrollment->save();

        $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);

        // Initialize progress data
        EnrollmentCreated::dispatch($freshEnrollment);

        // Send emails
        $this->sendEnrollmentEmails($freshEnrollment, $course, $user, $status);

        return [
            'enrollment' => $freshEnrollment,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Cancel pending enrollment request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function cancel(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== 'pending') {
            throw ValidationException::withMessages([
                'enrollment' => 'Hanya enrollment dengan status pending yang dapat dibatalkan.',
            ]);
        }

        $enrollment->status = 'cancelled';
        // Keep enrolled_at as is (cannot be null per migration)
        $enrollment->completed_at = null;
        $enrollment->save();

        return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
    }

    /**
     * Withdraw from an active course.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function withdraw(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== 'active') {
            throw ValidationException::withMessages([
                'enrollment' => 'Hanya enrollment aktif yang dapat mengundurkan diri.',
            ]);
        }

        $enrollment->status = 'cancelled';
        $enrollment->completed_at = null;
        $enrollment->save();

        return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
    }

    /**
     * Approve a pending enrollment.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function approve(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== 'pending') {
            throw ValidationException::withMessages([
                'enrollment' => 'Hanya permintaan enrollment pending yang dapat disetujui.',
            ]);
        }

        $enrollment->status = 'active';
        $enrollment->enrolled_at = Carbon::now();
        $enrollment->completed_at = null;
        $enrollment->save();

        $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);
        $course = $freshEnrollment->course;
        $student = $freshEnrollment->user;

        // Send approval email to student
        if ($student && $course) {
            $courseUrl = $this->getCourseUrl($course);
            Mail::to($student->email)->send(new StudentEnrollmentApprovedMail($student, $course, $courseUrl));
        }

        return $freshEnrollment;
    }

    /**
     * Decline a pending enrollment.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function decline(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== 'pending') {
            throw ValidationException::withMessages([
                'enrollment' => 'Hanya permintaan enrollment pending yang dapat ditolak.',
            ]);
        }

        $enrollment->status = 'cancelled';
        // Keep enrolled_at as is (cannot be null per migration)
        $enrollment->completed_at = null;
        $enrollment->save();

        $freshEnrollment = $enrollment->fresh(['course:id,title,slug,code', 'user:id,name,email']);
        $course = $freshEnrollment->course;
        $student = $freshEnrollment->user;

        // Send decline email to student
        if ($student && $course) {
            Mail::to($student->email)->send(new StudentEnrollmentDeclinedMail($student, $course));
        }

        return $freshEnrollment;
    }

    /**
     * Remove an enrollment from a course.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function remove(Enrollment $enrollment): Enrollment
    {
        if (! in_array($enrollment->status, ['active', 'pending'], true)) {
            throw ValidationException::withMessages([
                'enrollment' => 'Hanya enrollment aktif atau pending yang dapat dikeluarkan.',
            ]);
        }

        $enrollment->status = 'cancelled';
        // Keep enrolled_at as is (cannot be null per migration)
        $enrollment->completed_at = null;
        $enrollment->save();

        return $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function determineStatusAndMessage(Course $course, array $payload): array
    {
        $type = $course->enrollment_type ?? 'auto_accept';

        return match ($type) {
            'auto_accept' => ['active', 'Enrol berhasil. Anda sekarang terdaftar pada course ini.'],
            'key_based' => $this->handleKeyBasedEnrollment($course, $payload),
            'approval' => ['pending', 'Permintaan enrollment berhasil dikirim. Menunggu persetujuan.'],
            default => ['active', 'Enrol berhasil.'],
        };
    }

    /**
     * @return array{0: string, 1: string}
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function handleKeyBasedEnrollment(Course $course, array $payload): array
    {
        $providedKey = trim((string) ($payload['enrollment_key'] ?? ''));

        if ($providedKey === '') {
            throw ValidationException::withMessages([
                'enrollment_key' => 'Kode enrollment wajib diisi.',
            ]);
        }

        if (! hash_equals((string) $course->enrollment_key, $providedKey)) {
            throw ValidationException::withMessages([
                'enrollment_key' => 'Kode enrollment tidak valid.',
            ]);
        }

        return ['active', 'Enrol berhasil menggunakan kode kunci.'];
    }

    /**
     * Send enrollment notification emails to student and admins/instructors.
     */
    private function sendEnrollmentEmails(Enrollment $enrollment, Course $course, User $student, string $status): void
    {
        // Send email to student based on status
        if ($status === 'active') {
            $courseUrl = $this->getCourseUrl($course);
            Mail::to($student->email)->send(new StudentEnrollmentActiveMail($student, $course, $courseUrl));
        } elseif ($status === 'pending') {
            Mail::to($student->email)->send(new StudentEnrollmentPendingMail($student, $course));
        }

        // Send notification to course admins and instructor
        $this->notifyCourseManagers($enrollment, $course, $student);
    }

    /**
     * Notify all course managers (admins and instructor) about new enrollment.
     */
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
     * Get all course managers (instructor + admins).
     *
     * @return array<int, User>
     */
    private function getCourseManagers(Course $course): array
    {
        $managers = [];
        $managerIds = [];

        // Load fresh course with relationships
        $course = $course->fresh(['instructor', 'admins']);

        // Load instructor
        if ($course->instructor_id && $course->instructor) {
            $instructor = $course->instructor;
            $managers[] = $instructor;
            $managerIds[] = $instructor->id;
        }

        // Load admins
        foreach ($course->admins as $admin) {
            if ($admin && ! in_array($admin->id, $managerIds, true)) {
                $managers[] = $admin;
                $managerIds[] = $admin->id;
            }
        }

        return $managers;
    }

    /**
     * Generate course URL for frontend.
     */
    private function getCourseUrl(Course $course): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        return rtrim($frontendUrl, '/') . '/courses/' . $course->slug;
    }

    /**
     * Generate enrollments management URL for frontend.
     */
    private function getEnrollmentsUrl(Course $course): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        return rtrim($frontendUrl, '/') . '/courses/' . $course->slug . '/enrollments';
    }
}


