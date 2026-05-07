<?php

declare(strict_types=1);

namespace Modules\Gamification\Listeners;

use Modules\Auth\Events\ProfileUpdated;
use Modules\Gamification\Services\EventCounterService;
use Modules\Gamification\Services\Support\BadgeRuleEvaluator;

class AwardGamificationForProfileUpdated extends GamificationListener
{
    public function __construct(
        private readonly EventCounterService $counterService,
        private readonly BadgeRuleEvaluator $evaluator
    ) {}

    public function handle(ProfileUpdated $event): void
    {
        $user = $event->user;

        $this->counterService->incrementGlobal($user->id, 'profile_updated');

        $this->evaluator->evaluate($user, 'profile_updated', []);
    }
}
