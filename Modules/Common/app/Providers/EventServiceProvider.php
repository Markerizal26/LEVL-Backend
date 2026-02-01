<?php

declare(strict_types=1);

namespace Modules\Common\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Common\Listeners\LogAnswerKeyChanged;
use Modules\Common\Listeners\LogGradeCreated;
use Modules\Common\Listeners\LogGradeOverridden;
use Modules\Common\Listeners\LogOverrideGranted;
use Modules\Common\Listeners\LogSubmissionCreated;
use Modules\Common\Listeners\LogSubmissionStateChanged;
use Modules\Grading\Events\GradeCreated;
use Modules\Grading\Events\GradeOverridden;
use Modules\Learning\Events\AnswerKeyChanged;
use Modules\Learning\Events\OverrideGranted;
use Modules\Learning\Events\SubmissionCreated;
use Modules\Learning\Events\SubmissionStateChanged;

/**
 * Event service provider for the Common module.
 *
 * Registers audit logging listeners for all critical operations.
 *
 * Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 24.5
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for audit logging.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Submission events (Requirements 20.1)
        SubmissionCreated::class => [
            LogSubmissionCreated::class,
        ],
        SubmissionStateChanged::class => [
            LogSubmissionStateChanged::class,
        ],

        // Grading events (Requirements 20.2, 20.4)
        GradeCreated::class => [
            LogGradeCreated::class,
        ],
        GradeOverridden::class => [
            LogGradeOverridden::class,
        ],

        // Answer key events (Requirements 20.3)
        AnswerKeyChanged::class => [
            LogAnswerKeyChanged::class,
        ],



        // Override events (Requirements 24.5)
        OverrideGranted::class => [
            LogOverrideGranted::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
