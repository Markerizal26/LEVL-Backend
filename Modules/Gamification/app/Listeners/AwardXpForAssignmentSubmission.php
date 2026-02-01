<?php

namespace Modules\Gamification\Listeners;

use Modules\Common\Models\SystemSetting;
use Modules\Gamification\Services\GamificationService;
use Modules\Learning\Events\SubmissionCreated;

class AwardXpForAssignmentSubmission
{
    public function __construct(private GamificationService $gamification) {}

    public function handle(SubmissionCreated $event): void
    {
        $submission = $event->submission->fresh(['assignment.lesson']);

        if (! $submission) {
            return;
        }

        $xp = (int) SystemSetting::get('gamification.points.assignment_submit', 20);

        $this->gamification->awardXp(
            $submission->user_id,
            $xp,
            'completion',
            'assignment',
            $submission->id,
            [
                'description' => sprintf('Participation: Submitted assignment #%d', $submission->assignment_id),
                'allow_multiple' => true, 
            ]
        );
    }
}

