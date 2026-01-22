<?php

namespace Modules\Notifications\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Grading\Events\AppealDecided;
use Modules\Grading\Events\AppealSubmitted;
use Modules\Grading\Events\GradeRecalculated;
use Modules\Grading\Events\GradesReleased;
use Modules\Learning\Events\SubmissionStateChanged;
use Modules\Notifications\Listeners\NotifyOnAppealDecided;
use Modules\Notifications\Listeners\NotifyOnAppealSubmitted;
use Modules\Notifications\Listeners\NotifyOnGradeRecalculated;
use Modules\Notifications\Listeners\NotifyOnGradesReleased;
use Modules\Notifications\Listeners\NotifyOnSubmissionStateChanged;
use Modules\Notifications\Listeners\NotifyUserOnCourseCompleted;
use Modules\Schemes\Events\CourseCompleted;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Course completion notifications
        CourseCompleted::class => [
            NotifyUserOnCourseCompleted::class,
        ],

        // Grading notifications - Requirements 21.1, 21.3
        // Notify students when submissions are graded
        // Notify instructors when manual grading is required
        SubmissionStateChanged::class => [
            NotifyOnSubmissionStateChanged::class,
        ],

        // Grade release notifications - Requirements 14.6, 21.2
        // Notify students when grades are released in deferred mode
        GradesReleased::class => [
            NotifyOnGradesReleased::class,
        ],

        // Appeal notifications - Requirements 17.3, 21.4
        // Notify instructors when an appeal is submitted
        AppealSubmitted::class => [
            NotifyOnAppealSubmitted::class,
        ],

        // Appeal decision notifications - Requirements 17.5, 21.5
        // Notify students when an appeal is decided
        AppealDecided::class => [
            NotifyOnAppealDecided::class,
        ],

        // Grade recalculation notifications - Requirements 15.5
        // Notify students when their grades are recalculated
        GradeRecalculated::class => [
            NotifyOnGradeRecalculated::class,
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
