<?php

namespace Modules\Enrollments\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

class StudentEnrollmentActiveMail extends Mailable
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
            ->view('enrollments::emails.student-enrollment-active')
            ->with([
                'student' => $this->student,
                'course' => $this->course,
                'courseUrl' => $this->courseUrl,
            ]);
    }
}

