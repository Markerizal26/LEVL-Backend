<?php

declare(strict_types=1);

namespace Modules\Gamification\Services\Support;

use Illuminate\Support\Facades\DB;
use Modules\Gamification\Models\UserGamificationStat;
use Modules\Gamification\Repositories\GamificationRepository;

class LeaderboardManager
{
    public function __construct(
        private readonly GamificationRepository $repository
    ) {}

    public function updateGlobalLeaderboard(): void
    {
        $stats = $this->repository->globalLeaderboardStats();

        DB::transaction(function () use ($stats) {
            $rank = 1;
            $userIds = $stats->pluck('user_id')->toArray();

            foreach ($stats as $stat) {
                $this->repository->upsertLeaderboard(null, $stat->user_id, $rank++);
            }

            $this->repository->deleteGlobalLeaderboardExcept($userIds);
        });
    }
}
