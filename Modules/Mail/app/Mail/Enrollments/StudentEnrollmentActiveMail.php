<?php

declare(strict_types=1);

namespace Modules\Mail\Mail\Enrollments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

class StudentEnrollmentActiveMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $student,
        public readonly Course $course,
        public readonly string $courseUrl
    ) {}

    public function build(): self
    {
        return $this->subject('Enrollment Berhasil - ' . $this->course->title)
            ->view('mail::emails.enrollments.student-enrollment-active')
            ->with([
                'student' => $this->student,
                'course' => $this->course,
                'courseUrl' => $this->courseUrl,
            ]);
    }
}
