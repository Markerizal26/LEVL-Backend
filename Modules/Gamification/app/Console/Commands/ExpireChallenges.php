<?php

namespace Modules\Gamification\Console\Commands;

use Illuminate\Console\Command;
use Modules\Gamification\Services\ChallengeService;

class ExpireChallenges extends Command
{
    protected $signature = 'challenges:expire';

    protected $description = 'Mark overdue challenges as expired';

    public function handle(ChallengeService $challengeService): int
    {
        $this->info(__('messages.challenges.expiring'));

        $count = $challengeService->expireOverdueChallenges();

        $this->info(__('messages.challenges.expired_count', ['count' => $count]));

        return self::SUCCESS;
    }
}
