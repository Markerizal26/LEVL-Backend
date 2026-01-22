<?php

declare(strict_types=1);

namespace Modules\Learning\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Modules\Learning\Events\SubmissionCreated::class => [
            
            
            
        ],
        \Modules\Learning\Events\AssignmentPublished::class => [
            \Modules\Learning\Listeners\NotifyEnrolledUsersOnAssignmentPublished::class,
        ],
        \Modules\Learning\Events\NewHighScoreAchieved::class => [
            \Modules\Learning\Listeners\RecalculateCourseGradeOnNewHighScore::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
