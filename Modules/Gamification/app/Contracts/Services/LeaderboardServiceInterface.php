<?php

namespace Modules\Gamification\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LeaderboardServiceInterface
{
    public function getGlobalLeaderboard(int $perPage = 10, int $page = 1, ?int $courseId = null): LengthAwarePaginator;

    public function getUserRank(int $userId): array;

    public function updateRankings(): void;
}
