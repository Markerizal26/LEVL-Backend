<?php

declare(strict_types=1);

namespace Modules\Mail\Mail\Learning;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;
use Modules\Schemes\Models\Course;

class AssignmentPublishedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Course $course,
        public readonly Assignment $assignment,
        public readonly string $courseUrl,
        public readonly string $assignmentUrl
    ) {}

    public function build(): self
    {
        return $this->subject('Assignment Baru: ' . $this->assignment->title . ' - ' . $this->course->title)
            ->view('mail::emails.learning.assignment-published')
            ->with([
                'user' => $this->user,
                'course' => $this->course,
                'assignment' => $this->assignment,
                'courseUrl' => $this->courseUrl,
                'assignmentUrl' => $this->assignmentUrl,
            ]);
    }
}
