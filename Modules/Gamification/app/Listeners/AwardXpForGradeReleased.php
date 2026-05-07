<?php

declare(strict_types=1);

namespace Modules\Gamification\Listeners;

use Modules\Common\Models\SystemSetting;
use Modules\Gamification\Services\EventCounterService;
use Modules\Gamification\Services\GamificationService;
use Modules\Grading\Events\GradesReleased;

class AwardXpForGradeReleased extends GamificationListener
{
    public function __construct(
        private readonly GamificationService $gamification,
        private readonly EventCounterService $counterService,
        private readonly \Modules\Gamification\Services\Support\BadgeRuleEvaluator $evaluator
    ) {}

    public function handle(GradesReleased $event): void
    {
        $xpAmount = (int) SystemSetting::get('gamification.points.assignment_completion', 50);

        foreach ($event->submissions as $submission) {
            $grade = $submission->grade;
            $assignment = $submission->assignment;

            if (! $grade || ! $grade->isReleased()) {
                continue;
            }

            $passingGrade = $assignment->passing_grade;

            if ($grade->effective_score < $passingGrade) {
                continue;
            }

            $this->gamification->awardXp(
                $submission->user_id,
                $xpAmount,
                'assignment_completed',
                'assignment',
                $assignment->id,
                [
                    'description' => 'Assignment completion XP',
                    'allow_multiple' => false,
                ]
            );

            $this->counterService->incrementGlobal($submission->user_id, 'assignment_graded');

            $user = $this->getCachedUser($submission->user_id);
            if ($user) {
                $payload = [
                    'assignment_id' => $assignment->id,
                    'course_id' => $assignment->course_id,
                    'score' => $grade->effective_score,
                    'attempts' => $submission->attempt,
                    'is_first_submission' => $submission->attempt === 1,
                    'time' => $submission->created_at->format('H:i:s'),
                ];
                $this->evaluator->evaluate($user, 'assignment_graded', $payload);
            }
        }
    }
}
