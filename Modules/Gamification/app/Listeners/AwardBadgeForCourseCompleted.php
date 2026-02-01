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

        if (! $enrollment || ! $enrollment->user || ! $course) {
            return;
        }

        $badgeCode = sprintf('course_completion_%d', $course->id);
        $badgeName = sprintf('Menyelesaikan: %s', $course->title);

        $this->gamification->awardBadge(
            $enrollment->user_id,
            $badgeCode,
            $badgeName,
            sprintf('Berhasil menyelesaikan course "%s"', $course->title)
        );

        $completionXp = (int) \Modules\Common\Models\SystemSetting::get('gamification.points.course_complete', 50);

        if ($completionXp > 0) {
            $this->gamification->awardXp(
                $enrollment->user_id,
                $completionXp,
                'bonus',
                'system',
                $course->id,
                [
                    'description' => sprintf('Menyelesaikan course: %s', $course->title),
                    'allow_multiple' => false,
                ]
            );
        }
    }
}
