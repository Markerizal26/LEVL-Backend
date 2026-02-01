<?php

declare(strict_types=1);

namespace Modules\Gamification\Services\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Gamification\Models\Point;
use Modules\Gamification\Models\UserGamificationStat;
use Modules\Gamification\Repositories\GamificationRepository;

class PointManager
{
    public function __construct(
        private readonly GamificationRepository $repository
    ) {}

    public function awardXp(
        int $userId,
        int $points,
        string $reason,
        ?string $sourceType = null,
        ?int $sourceId = null,
        array $options = []
    ): ?Point {
        if ($points <= 0) {
            return null;
        }

        $allowMultiple = (bool) ($options['allow_multiple'] ?? true);

        return DB::transaction(function () use ($userId, $points, $reason, $sourceType, $sourceId, $options, $allowMultiple) {
            if (! $allowMultiple && $this->repository->pointExists($userId, $sourceType, $sourceId, $reason)) {
                return null;
            }

            $resolvedSourceType = $sourceType ?? 'system';

            $point = $this->repository->createPoint([
                'user_id' => $userId,
                'points' => $points,
                'reason' => $reason,
                'source_type' => $resolvedSourceType,
                'source_id' => $sourceId,
                'description' => $options['description'] ?? null,
            ]);

            $this->updateUserGamificationStats($userId, $points);

            return $point;
        });
    }

    private function updateUserGamificationStats(int $userId, int $points): UserGamificationStat
    {
        $stats = $this->repository->getOrCreateStats($userId);
        $stats->total_xp += $points;
        $stats->global_level = $this->calculateLevelFromXp($stats->total_xp);
        $stats->stats_updated_at = Carbon::now();
        $stats->last_activity_date = Carbon::now()->startOfDay();

        return $this->repository->saveStats($stats);
    }

    public function calculateLevelFromXp(int $totalXp): int
    {
        return $this->calculateLevelRecursive($totalXp, 0);
    }

    private function calculateLevelRecursive(int $remainingXp, int $level): int
    {
        $xpRequired = (int) round(100 * pow(1.1, $level));

        if ($xpRequired <= 0 || $remainingXp < $xpRequired) {
            return $level;
        }

        return $this->calculateLevelRecursive($remainingXp - $xpRequired, $level + 1);
    }

    public function getOrCreateStats(int $userId): UserGamificationStat
    {
        return $this->repository->getOrCreateStats($userId);
    }

    public function getPointsHistory(int $userId, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->repository->paginateByUserId($userId, $perPage);
    }

    public function getAchievements(int $totalXp, int $currentLevel): array
    {
        $milestones = \Modules\Gamification\Models\Milestone::active()
            ->ordered()
            ->get();

        $achievements = $milestones->map(function ($milestone) use ($totalXp) {
            $achieved = $totalXp >= $milestone->xp_required;
            $progress = min(100, ($totalXp / $milestone->xp_required) * 100);

            return [
                'name' => $milestone->name,
                'xp_required' => $milestone->xp_required,
                'level_required' => $milestone->level_required,
                'achieved' => $achieved,
                'progress' => round($progress, 2),
            ];
        });

        $nextMilestone = $achievements->first(fn ($m) => ! $m['achieved']);

        return [
            'achievements' => $achievements,
            'next_milestone' => $nextMilestone,
            'current_xp' => $totalXp,
            'current_level' => $currentLevel,
        ];
    }
}
