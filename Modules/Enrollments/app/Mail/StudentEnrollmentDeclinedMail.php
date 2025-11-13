<?php

namespace Modules\Enrollments\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

class StudentEnrollmentDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $student,
        public readonly Course $course
    ) {}

    public function build(): self
    {
        return $this->subject('Permintaan Enrollment Ditolak - ' . $this->course->title)
            ->view('enrollments::emails.student-enrollment-declined')
            ->with([
                'student' => $this->student,
                'course' => $this->course,
            ]);
    }
}

