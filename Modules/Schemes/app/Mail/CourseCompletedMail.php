<?php

namespace Modules\Schemes\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class CourseCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Course $course,
        public readonly Enrollment $enrollment,
        public readonly string $courseUrl
    ) {}

    public function build(): self
    {
        return $this->subject('Selamat! Anda Telah Menyelesaikan Course: ' . $this->course->title)
            ->view('schemes::emails.course-completed')
            ->with([
                'user' => $this->user,
                'course' => $this->course,
                'enrollment' => $this->enrollment,
                'courseUrl' => $this->courseUrl,
            ]);
    }
}

