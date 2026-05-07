<?php

declare(strict_types=1);

namespace Modules\Gamification\Listeners;

use Modules\Gamification\Services\EventCounterService;
use Modules\Gamification\Services\GamificationService;
use Modules\Schemes\Events\CourseCompleted;

class AwardBadgeForCourseCompleted extends GamificationListener
{
    public function __construct(
        private GamificationService $gamification,
        private EventCounterService $counterService,
        private \Modules\Gamification\Services\Support\BadgeRuleEvaluator $evaluator
    ) {}

    public function handle(CourseCompleted $event): void
    {

        $enrollment = $event->enrollment;
        $course = $event->course;

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
                'course_completed',
                'course',
                $course->id,
                [
                    'description' => sprintf('Menyelesaikan course: %s', $course->title),
                    'allow_multiple' => false,
                ]
            );
        }

        $this->counterService->incrementGlobal($enrollment->user_id, 'course_completed');

        $payload = [
            'course_id' => $course->id,
            'course_slug' => $course->slug,
            'duration_days' => $enrollment->created_at->diffInDays($enrollment->completed_at ?? now()),
        ];
        $this->evaluator->evaluate($enrollment->user, 'course_completed', $payload);
    }
}
