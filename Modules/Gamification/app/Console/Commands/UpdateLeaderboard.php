<?php

namespace Modules\Gamification\Console\Commands;

use Illuminate\Console\Command;
use Modules\Gamification\Services\LeaderboardService;

class UpdateLeaderboard extends Command
{
    protected $signature = 'leaderboard:update';

    protected $description = 'Update global leaderboard rankings';

    public function handle(LeaderboardService $leaderboardService): int
    {
        $this->info(__('messages.gamification.updating_leaderboard'));

        $leaderboardService->updateRankings();

        $this->info(__('messages.gamification.leaderboard_updated'));

        return self::SUCCESS;
    }
}
