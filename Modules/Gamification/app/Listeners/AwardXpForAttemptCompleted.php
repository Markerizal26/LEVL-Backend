<?php

namespace Modules\Gamification\Listeners;

use Modules\Assessments\Events\AttemptCompleted;
use Modules\Common\Models\SystemSetting;
use Modules\Gamification\Services\GamificationService;

class AwardXpForAttemptCompleted
{
    public function __construct(private GamificationService $gamification) {}

    public function handle(AttemptCompleted $event): void
    {
        $attempt = $event->attempt->fresh();

        if (! $attempt) {
            return;
        }

        $xp = (int) SystemSetting::get('gamification.points.quiz_complete', 30);

        $this->gamification->awardXp(
            $attempt->user_id,
            $xp,
            'completion',
            'attempt',
            $attempt->id,
            [
                'description' => sprintf('Completed exercise attempt #%d', $attempt->id),
                'allow_multiple' => true,
            ]
        );
    }
}

