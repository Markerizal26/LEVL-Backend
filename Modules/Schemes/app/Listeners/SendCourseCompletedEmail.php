<?php

namespace Modules\Schemes\Listeners;

use Illuminate\Support\Facades\Mail;
use Modules\Schemes\Events\CourseCompleted;
use Modules\Schemes\Mail\CourseCompletedMail;

class SendCourseCompletedEmail
{
    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment->fresh(['user', 'course']);

        if (! $enrollment->user || ! $enrollment->user->email) {
            return;
        }

        $course = $event->course;
        $user = $enrollment->user;

        $courseUrl = $this->getCourseUrl($course);

        Mail::to($user->email)->send(
            new CourseCompletedMail($user, $course, $enrollment, $courseUrl)
        );
    }

    private function getCourseUrl($course): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        return rtrim($frontendUrl, '/') . '/courses/' . $course->slug;
    }
}

