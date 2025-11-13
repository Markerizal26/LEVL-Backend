<?php

namespace Modules\Gamification\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Schemes\Events\LessonCompleted::class => [
            \Modules\Gamification\Listeners\AwardXpForLessonCompleted::class,
        ],
        \Modules\Learning\Events\SubmissionCreated::class => [
            \Modules\Gamification\Listeners\AwardXpForAssignmentSubmission::class,
        ],
        \Modules\Assessments\Events\AttemptCompleted::class => [
            \Modules\Gamification\Listeners\AwardXpForAttemptCompleted::class,
        ],
        \Modules\Schemes\Events\CourseCompleted::class => [
            \Modules\Gamification\Listeners\AwardBadgeForCourseCompleted::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
