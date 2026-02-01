<?php

namespace Modules\Gamification\Console\Commands;

use Illuminate\Console\Command;
use Modules\Gamification\Services\ChallengeService;

class AssignWeeklyChallenges extends Command
{
    protected $signature = 'challenges:assign-weekly';

    protected $description = 'Assign weekly challenges to all active users';

    public function handle(ChallengeService $challengeService): int
    {
        $this->info(__('messages.challenges.assigning_weekly'));

        $count = $challengeService->assignWeeklyChallenges();

        $this->info(__('messages.challenges.assigned_weekly', ['count' => $count]));

        return self::SUCCESS;
    }
}
