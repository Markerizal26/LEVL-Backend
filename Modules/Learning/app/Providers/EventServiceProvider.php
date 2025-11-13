<?php

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
