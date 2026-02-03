<?php

declare(strict_types=1);

namespace Modules\Mail\Mail\Enrollments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

class AdminEnrollmentNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $admin,
        public readonly User $student,
        public readonly Course $course,
        public readonly string $enrollmentUrl
    ) {}

    public function build(): self
    {
        return $this->subject('New Enrollment Pending Review - ' . $this->course->title)
            ->view('mail::emails.enrollments.admin-enrollment-notification')
            ->with([
                'admin' => $this->admin,
                'student' => $this->student,
                'course' => $this->course,
                'enrollmentUrl' => $this->enrollmentUrl,
            ]);
    }
}
