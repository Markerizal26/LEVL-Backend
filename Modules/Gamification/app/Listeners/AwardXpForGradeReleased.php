<?php

namespace Modules\Gamification\Listeners;

use Modules\Gamification\Services\GamificationService;
use Modules\Grading\Events\GradesReleased;

class AwardXpForGradeReleased
{
    public function __construct(private GamificationService $gamification) {}

    public function handle(GradesReleased $event): void
    {
        
        
        
        foreach ($event->submissions as $submission) {
            $grade = $submission->grade;

            if (! $grade || ! $grade->isReleased()) {
                continue;
            }

            $score = $grade->effective_score; 
            $xp = $this->calculateXpFromScore($score);

            $this->gamification->awardXp(
                $submission->user_id,
                $xp,
                'achievement',
                'grade',
                $grade->id,
                [
                    'description' => sprintf(
                        'Achievement XP for Assignment #%d (Score: %.1f)',
                        $submission->assignment_id,
                        $score
                    ),
                    'allow_multiple' => false, 
                ]
            );
        }
    }

    private function calculateXpFromScore(float $score): int
    {
        if ($score >= 90) {
            return 100;
        }
        if ($score >= 80) {
            return 75;
        }
        if ($score >= 70) {
            return 50;
        }
        if ($score >= 60) {
            return 25;
        }
        return 10;
    }
}
