<?php

namespace Modules\Enrollments\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class AdminEnrollmentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $admin,
        public readonly User $student,
        public readonly Course $course,
        public readonly Enrollment $enrollment,
        public readonly string $enrollmentsUrl
    ) {}

    public function build(): self
    {
        $subject = $this->enrollment->status === EnrollmentStatus::Pending
            ? 'Permintaan Enrollment Baru - '.$this->course->title
            : 'Enrollment Baru - '.$this->course->title;

        return $this->subject($subject)
            ->view('enrollments::emails.admin-enrollment-notification')
            ->with([
                'admin' => $this->admin,
                'student' => $this->student,
                'course' => $this->course,
                'enrollment' => $this->enrollment,
                'enrollmentsUrl' => $this->enrollmentsUrl,
            ]);
    }
}
