<?php

declare(strict_types=1);

namespace Modules\Learning\Listeners;

use Illuminate\Support\Facades\Mail;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Events\AssignmentPublished;
use Modules\Learning\Mail\AssignmentPublishedMail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifyEnrolledUsersOnAssignmentPublished implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment->fresh(['lesson.unit.course']);

        if (! $assignment->lesson || ! $assignment->lesson->unit || ! $assignment->lesson->unit->course) {
            return;
        }

        $course = $assignment->lesson->unit->course;

        $courseUrl = $this->getCourseUrl($course);
        $assignmentUrl = $this->getAssignmentUrl($course, $assignment);

        
        Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active->value)
            ->with(['user:id,name,email'])
            ->chunkById(100, function ($enrollments) use ($course, $assignment, $courseUrl, $assignmentUrl) {
                foreach ($enrollments as $enrollment) {
                    if ($enrollment->user && $enrollment->user->email) {
                        
                        Mail::to($enrollment->user->email)->queue(
                            new AssignmentPublishedMail(
                                $enrollment->user,
                                $course,
                                $assignment,
                                $courseUrl,
                                $assignmentUrl,
                            ),
                        );
                    }
                }
            });
    }

    private function getCourseUrl($course): string
    {
        $frontendUrl = config('app.frontend_url');

        return rtrim($frontendUrl, '/').'/courses/'.$course->slug;
    }

    private function getAssignmentUrl($course, $assignment): string
    {
        $frontendUrl = config('app.frontend_url');

        return rtrim($frontendUrl, '/').
          '/courses/'.
          $course->slug.
          '/assignments/'.
          $assignment->id;
    }
}
