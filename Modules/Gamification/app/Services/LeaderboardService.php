<?php

namespace Modules\Gamification\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Gamification\Contracts\Services\LeaderboardServiceInterface;
use Modules\Gamification\Models\Leaderboard;
use Modules\Gamification\Models\UserGamificationStat;
use Modules\Gamification\Models\UserScopeStat;

class LeaderboardService implements LeaderboardServiceInterface
{
    
    public function getGlobalLeaderboard(int $perPage = 10, int $page = 1, ?int $courseId = null): LengthAwarePaginator
    {
        $perPage = min($perPage, 100); 

        $query = $courseId
            ? \Modules\Gamification\Models\Leaderboard::with(['user:id,name', 'user.media'])
                ->where('course_id', $courseId)
                ->orderBy('rank', 'asc')
            : UserGamificationStat::with(['user:id,name', 'user.media'])
                ->orderByDesc('total_xp')
                ->orderBy('user_id');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getUserRank(int $userId): array
    {
        $userStats = UserGamificationStat::where('user_id', $userId)->first();

        if (! $userStats) {
            return [
                'rank' => null,
                'total_xp' => 0,
                'level' => 0,
                'surrounding' => [],
            ];
        }

        $rank = UserGamificationStat::where('total_xp', '>', $userStats->total_xp)->count() + 1;

        $surrounding = $this->getSurroundingUsers($userId, $userStats->total_xp, 2);

        return [
            'rank' => $rank,
            'total_xp' => $userStats->total_xp,
            'level' => $userStats->global_level,
            'surrounding' => $surrounding,
        ];
    }

    public function updateRankings(): void
    {
        
        $stats = UserGamificationStat::orderByDesc('total_xp')
            ->orderBy('user_id')
            ->get();

        DB::transaction(function () use ($stats) {
            $rank = 1;
            $userIds = $stats->pluck('user_id')->toArray();

            foreach ($stats as $stat) {
                Leaderboard::updateOrCreate(
                    [
                        'course_id' => null,
                        'user_id' => $stat->user_id,
                    ],
                    ['rank' => $rank++]
                );
            }

            if (! empty($userIds)) {
                Leaderboard::whereNull('course_id')
                    ->whereNotIn('user_id', $userIds)
                    ->delete();
            }

            
            $this->updateCourseRankings();
        });
    }

    private function updateCourseRankings(): void
    {
        
        
        
        
        
        $courses = UserScopeStat::where('scope_type', 'course')
            ->select('scope_id')
            ->distinct()
            ->pluck('scope_id');

        foreach ($courses as $courseId) {
            $courseStats = UserScopeStat::where('scope_type', 'course')
                ->where('scope_id', $courseId)
                ->orderByDesc('total_xp')
                ->orderBy('user_id')
                ->get();

            $rank = 1;
            $userIds = $courseStats->pluck('user_id')->toArray();

            foreach ($courseStats as $stat) {
                 Leaderboard::updateOrCreate(
                    [
                        'course_id' => $courseId,
                        'user_id' => $stat->user_id,
                    ],
                    ['rank' => $rank++]
                );
            }

            if (! empty($userIds)) {
                Leaderboard::where('course_id', $courseId)
                    ->whereNotIn('user_id', $userIds)
                    ->delete();
            }
        }
    }

    private function getSurroundingUsers(int $userId, int $userXp, int $count = 2): array
    {
        
        $above = UserGamificationStat::with(['user:id,name', 'user.media'])
            ->where('total_xp', '>', $userXp)
            ->orderBy('total_xp')
            ->limit($count)
            ->get()
            ->reverse()
            ->values();

        $below = UserGamificationStat::with(['user:id,name', 'user.media'])
            ->where('total_xp', '<', $userXp)
            ->orderByDesc('total_xp')
            ->limit($count)
            ->get();

        $current = UserGamificationStat::with(['user:id,name', 'user.media'])
            ->where('user_id', $userId)
            ->first();

        $result = [];

        foreach ($above as $stat) {
            $rank = UserGamificationStat::where('total_xp', '>', $stat->total_xp)->count() + 1;
            $result[] = $this->formatLeaderboardEntry($stat, $rank);
        }

        if ($current) {
            $rank = UserGamificationStat::where('total_xp', '>', $current->total_xp)->count() + 1;
            $result[] = array_merge($this->formatLeaderboardEntry($current, $rank), ['is_current_user' => true]);
        }

        foreach ($below as $stat) {
            $rank = UserGamificationStat::where('total_xp', '>', $stat->total_xp)->count() + 1;
            $result[] = $this->formatLeaderboardEntry($stat, $rank);
        }

        return $result;
    }

    private function formatLeaderboardEntry(UserGamificationStat $stat, int $rank): array
    {
        return [
            'rank' => $rank,
            'user' => [
                'id' => $stat->user_id,
                'name' => $stat->user?->name ?? 'Unknown',
                'avatar_url' => $stat->user?->avatar_url ?? null,
            ],
            'total_xp' => $stat->total_xp,
            'level' => $stat->global_level,
        ];
    }
}
