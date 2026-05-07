<?php

declare(strict_types=1);

namespace Modules\Gamification\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Gamification\Events\UserLeveledUp;
use Modules\Gamification\Services\EventCounterService;
use Modules\Gamification\Services\Support\BadgeManager;
use Modules\Gamification\Services\Support\BadgeRuleEvaluator;
use Modules\Gamification\Services\Support\PointManager;

class HandleLevelUp extends GamificationListener
{
    public function __construct(
        private readonly BadgeManager $badgeManager,
        private readonly PointManager $pointManager,
        private readonly EventCounterService $counterService,
        private readonly BadgeRuleEvaluator $evaluator
    ) {}

    public function handle(UserLeveledUp $event): void
    {
        try {

            $this->counterService->increment($event->userId, 'level_reached', 'global', null, 'lifetime');

            $user = $this->getCachedUser($event->userId);
            if ($user) {
                $this->evaluator->evaluate($user, 'level_reached', [
                    'level' => $event->newLevel,
                    'old_level' => $event->oldLevel,
                    'total_xp' => $event->totalXp,
                ]);
            }

            if (! empty($event->rewards)) {
                $this->awardRewards($event);
            }

            Log::info('User leveled up', [
                'user_id' => $event->userId,
                'old_level' => $event->oldLevel,
                'new_level' => $event->newLevel,
                'rewards' => $event->rewards,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to handle level up', [
                'user_id' => $event->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function awardRewards(UserLeveledUp $event): void
    {
        foreach ($event->rewards as $reward) {
            $type = $reward['type'] ?? null;

            if ($type === 'badge' && ! empty($reward['badge_code'])) {
                $this->badgeManager->awardBadge(
                    $event->userId,
                    $reward['badge_code'],
                    $reward['badge_name'] ?? "Level {$event->newLevel} Badge",
                    $reward['badge_description'] ?? null,
                );
            } elseif ($type === 'xp' && ! empty($reward['amount'])) {
                $this->pointManager->awardXp(
                    $event->userId,
                    (int) $reward['amount'],
                    'level_up_bonus',
                    'system',
                    null,
                    [
                        'description' => "Level {$event->newLevel} reward",
                        'metadata' => ['level' => $event->newLevel],
                    ]
                );
            }
        }
    }
}
