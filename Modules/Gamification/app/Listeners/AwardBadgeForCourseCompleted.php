<?php

namespace Modules\Gamification\Listeners;

use Modules\Gamification\Services\GamificationService;
use Modules\Schemes\Events\CourseCompleted;

class AwardBadgeForCourseCompleted
{
    public function __construct(private GamificationService $gamification) {}

    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment->fresh(['user']);
        $course = $event->course->fresh();

        if (! $enrollment || ! $enrollment->user) {
            return;
        }

        $this->gamification->awardBadge(
            $enrollment->user_id,
            'course_completion',
            'Course Completer',
            sprintf('Completed course: %s', $event->course->title)
        );

        // Optional XP bonus for finishing a course (configurable)
        $completionXp = (int) \Modules\Common\Models\SystemSetting::get('gamification.points.course_complete', 0);

        if ($completionXp > 0) {
            $this->gamification->awardXp(
                $enrollment->user_id,
                $completionXp,
                'bonus',
                'system',
                $event->course->id,
                [
                    'description' => sprintf('Completed course: %s', $event->course->title),
                    'allow_multiple' => false,
                ]
            );
        }
    }
}

