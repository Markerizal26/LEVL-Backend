<?php

declare(strict_types=1);

namespace Modules\Learning\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Learning\Events\SubmissionCreated::class => [
            // Grading module listener will be registered here when available
            // Gamification module listener will be registered here when available
            // Notifications module listener will be registered here when available
        ],
        \Modules\Learning\Events\AssignmentPublished::class => [
            \Modules\Learning\Listeners\NotifyEnrolledUsersOnAssignmentPublished::class,
        ],
        \Modules\Learning\Events\NewHighScoreAchieved::class => [
            \Modules\Learning\Listeners\RecalculateCourseGradeOnNewHighScore::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
