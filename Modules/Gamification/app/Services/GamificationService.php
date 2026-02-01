<?php

declare(strict_types=1);

namespace Modules\Gamification\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Modules\Gamification\Contracts\Services\ChallengeServiceInterface;
use Modules\Gamification\Contracts\Services\GamificationServiceInterface;
use Modules\Gamification\Contracts\Services\LeaderboardServiceInterface;
use Modules\Gamification\Models\Point;
use Modules\Gamification\Models\UserBadge;
use Modules\Gamification\Models\UserGamificationStat;
use Modules\Gamification\Repositories\GamificationRepository;
use Modules\Gamification\Services\Support\BadgeManager;
use Modules\Gamification\Services\Support\LeaderboardManager;
use Modules\Gamification\Services\Support\PointManager;

class GamificationService implements GamificationServiceInterface
{
    private readonly GamificationRepository $repository;

    public function __construct(
        private readonly PointManager $pointManager,
        private readonly BadgeManager $badgeManager,
        private readonly LeaderboardManager $leaderboardManager,
        private readonly ChallengeServiceInterface $challengeService,
        private readonly LeaderboardServiceInterface $leaderboardService,
        ?GamificationRepository $repository = null
    ) {
        $this->repository = $repository ?? app(GamificationRepository::class);
    }

    public function render(string $template, array $data = []): View
    {
        return view($this->repository->view($template), $data);
    }

    public function awardXp(
        int $userId,
        int $points,
        string $reason,
        ?string $sourceType = null,
        ?int $sourceId = null,
        array $options = []
    ): ?Point {
        return $this->pointManager->awardXp($userId, $points, $reason, $sourceType, $sourceId, $options);
    }

    public function awardBadge(
        int $userId,
        string $code,
        string $name,
        ?string $description = null
    ): ?UserBadge {
        return $this->badgeManager->awardBadge($userId, $code, $name, $description);
    }

    public function updateGlobalLeaderboard(): void
    {
        $this->leaderboardManager->updateGlobalLeaderboard();
    }

    public function getOrCreateStats(int $userId): UserGamificationStat
    {
        return $this->pointManager->getOrCreateStats($userId);
    }

    public function getUserBadges(int $userId): Collection
    {
        return $this->badgeManager->getUserBadges($userId);
    }

    public function countUserBadges(int $userId): int
    {
        return $this->badgeManager->countUserBadges($userId);
    }

    public function getPointsHistory(int $userId, int $perPage): LengthAwarePaginator
    {
        return $this->pointManager->getPointsHistory($userId, $perPage);
    }

    public function getAchievements(int $userId): array
    {
        $stats = $this->pointManager->getOrCreateStats($userId);
        return $this->pointManager->getAchievements($stats->total_xp, $stats->global_level);
    }

    public function getSummary(int $userId): array
    {
        $stats = $this->pointManager->getOrCreateStats($userId);
        $rankData = $this->leaderboardService->getUserRank($userId);
        $activeChallenges = $this->challengeService->getUserChallenges($userId)->count();
        $badgesCount = $this->badgeManager->countUserBadges($userId);

        return [
            'total_xp' => $stats->total_xp,
            'level' => $stats->global_level,
            'xp_to_next_level' => $stats->xp_to_next_level,
            'progress_to_next_level' => $stats->progress_to_next_level,
            'badges_count' => $badgesCount,
            'current_streak' => $stats->current_streak,
            'longest_streak' => $stats->longest_streak,
            'rank' => $rankData['rank'],
            'active_challenges' => $activeChallenges,
        ];
    }

    public function getUnitLevels(int $userId, int $courseId): Collection
    {
        return \Modules\Gamification\Models\UserScopeStat::where('user_id', $userId)
            ->where('scope_type', 'unit')
            ->whereIn('scope_id', function ($query) use ($courseId) {
                $query->select('id')
                    ->from('units')
                    ->where('course_id', $courseId);
            })
            ->get()
            ->map(function ($stat) {
                return [
                    'unit_id' => $stat->scope_id,
                    'level' => $stat->current_level,
                    'total_xp' => $stat->total_xp,
                    'progress' => 0, 
                ];
            });
    }
}
