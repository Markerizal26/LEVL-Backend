<?php

namespace Modules\Gamification\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        \Modules\Schemes\Events\LessonCompleted::class => [
            \Modules\Gamification\Listeners\AwardXpForLessonCompleted::class,
        ],
        \Modules\Schemes\Events\UnitCompleted::class => [
            \Modules\Gamification\Listeners\AwardXpForUnitCompleted::class,
        ],
        \Modules\Schemes\Events\CourseCompleted::class => [
            \Modules\Gamification\Listeners\AwardBadgeForCourseCompleted::class,
        ],

        \Modules\Learning\Events\SubmissionStateChanged::class => [
            \Modules\Gamification\Listeners\AwardXpForAssignmentSubmitted::class,
        ],
        \Modules\Learning\Events\QuizSubmitted::class => [
            \Modules\Gamification\Listeners\AwardXpForQuizSubmitted::class,
        ],
        \Modules\Learning\Events\QuizCompleted::class => [
            \Modules\Gamification\Listeners\AwardXpForQuizPassed::class,
        ],

        \Modules\Grading\Events\GradesReleased::class => [
            \Modules\Gamification\Listeners\AwardXpForGradeReleased::class,
            \Modules\Gamification\Listeners\AwardXpForPerfectScore::class,
        ],

        \Modules\Forums\Events\ThreadCreated::class => [
            \Modules\Gamification\Listeners\AwardXpForThreadCreated::class,
        ],
        \Modules\Forums\Events\ReplyCreated::class => [
            \Modules\Gamification\Listeners\AwardXpForReplyCreated::class,
        ],
        \Modules\Forums\Events\ReactionAdded::class => [
            \Modules\Gamification\Listeners\AwardXpForReactionReceived::class,
        ],

        \Modules\Gamification\Events\UserLeveledUp::class => [
            \Modules\Gamification\Listeners\HandleLevelUp::class,
        ],
        \Modules\Gamification\Events\UserLoggedIn::class => [
            \Modules\Gamification\Listeners\AwardXpForDailyLogin::class,
        ],
        \Modules\Gamification\Events\BadgeEarned::class => [
            \Modules\Gamification\Listeners\SendBadgeEarnedNotification::class,
        ],

        \Modules\Auth\Events\ProfileUpdated::class => [
            \Modules\Gamification\Listeners\AwardGamificationForProfileUpdated::class,
        ],

        \Modules\Forums\Events\ReplyAccepted::class => [
            \Modules\Gamification\Listeners\AwardGamificationForReplyAccepted::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
