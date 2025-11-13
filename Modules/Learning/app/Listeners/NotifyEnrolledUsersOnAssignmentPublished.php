<?php

namespace Modules\Learning\Listeners;

use Illuminate\Support\Facades\Mail;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Events\AssignmentPublished;
use Modules\Learning\Mail\AssignmentPublishedMail;

class NotifyEnrolledUsersOnAssignmentPublished
{
    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment->fresh(['lesson.unit.course']);

        if (! $assignment->lesson || ! $assignment->lesson->unit || ! $assignment->lesson->unit->course) {
            return;
        }

        $course = $assignment->lesson->unit->course;

        // Get all active enrollments for this course
        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->with(['user:id,name,email'])
            ->get();

        $courseUrl = $this->getCourseUrl($course);
        $assignmentUrl = $this->getAssignmentUrl($course, $assignment);

        foreach ($enrollments as $enrollment) {
            if ($enrollment->user && $enrollment->user->email) {
                Mail::to($enrollment->user->email)->send(
                    new AssignmentPublishedMail(
                        $enrollment->user,
                        $course,
                        $assignment,
                        $courseUrl,
                        $assignmentUrl
                    )
                );
            }
        }
    }

    private function getCourseUrl($course): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        return rtrim($frontendUrl, '/') . '/courses/' . $course->slug;
    }

    private function getAssignmentUrl($course, $assignment): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        return rtrim($frontendUrl, '/') . '/courses/' . $course->slug . '/assignments/' . $assignment->id;
    }
}

