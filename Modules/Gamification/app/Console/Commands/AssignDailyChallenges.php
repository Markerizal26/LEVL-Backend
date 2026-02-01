<?php

namespace Modules\Gamification\Console\Commands;

use Illuminate\Console\Command;
use Modules\Gamification\Services\ChallengeService;

class AssignDailyChallenges extends Command
{
    protected $signature = 'challenges:assign-daily';

    protected $description = 'Assign daily challenges to all active users';

    public function handle(ChallengeService $challengeService): int
    {
        $this->info(__('messages.challenges.assigning_daily'));

        $count = $challengeService->assignDailyChallenges();

        $this->info(__('messages.challenges.assigned_daily', ['count' => $count]));

        return self::SUCCESS;
    }
}
