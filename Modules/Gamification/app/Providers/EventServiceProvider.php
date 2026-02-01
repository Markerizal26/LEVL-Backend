<?php

namespace Modules\Gamification\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    
    protected $listen = [
        \Modules\Schemes\Events\LessonCompleted::class => [
            \Modules\Gamification\Listeners\AwardXpForLessonCompleted::class,
            \Modules\Gamification\Listeners\UpdateChallengeProgressOnLessonCompleted::class,
        ],
        \Modules\Learning\Events\SubmissionCreated::class => [
            \Modules\Gamification\Listeners\AwardXpForAssignmentSubmission::class,
            \Modules\Gamification\Listeners\UpdateChallengeProgressOnSubmissionCreated::class,
        ],
        \Modules\Schemes\Events\CourseCompleted::class => [
            \Modules\Gamification\Listeners\AwardBadgeForCourseCompleted::class,
        ],
        \Modules\Grading\Events\GradesReleased::class => [
            \Modules\Gamification\Listeners\AwardXpForGradeReleased::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
