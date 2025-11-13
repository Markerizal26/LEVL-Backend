<?php

namespace Modules\Gamification\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Gamification\Models\Badge;
use Modules\Gamification\Models\Leaderboard;
use Modules\Gamification\Models\Point;
use Modules\Gamification\Models\UserBadge;
use Modules\Gamification\Models\UserGamificationStat;

class GamificationService
{
    /**
     * Award XP to a user and update their gamification stats.
     *
     * @param  int  $userId
     * @param  int  $xp
     * @param  string  $reason
     * @param  string|null  $sourceType
     * @param  int|null  $sourceId
     * @param  array{allow_multiple?: bool, description?: string}  $options
     */
    public function awardXp(
        int $userId,
        int $xp,
        string $reason,
        ?string $sourceType = null,
        ?int $sourceId = null,
        array $options = []
    ): ?Point {
        if ($xp <= 0) {
            return null;
        }

        $allowMultiple = $options['allow_multiple'] ?? false;
        $allowedReasons = ['completion', 'score', 'bonus', 'penalty'];
        if (! in_array($reason, $allowedReasons, true)) {
            $reason = 'completion';
        }

        $allowedSources = ['lesson', 'assignment', 'attempt', 'system'];
        if ($sourceType && ! in_array($sourceType, $allowedSources, true)) {
            $sourceType = 'system';
        }

        if (! $allowMultiple && $sourceType && $sourceId) {
            $exists = Point::query()
                ->where('user_id', $userId)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->exists();

            if ($exists) {
                return null;
            }
        }

        return DB::transaction(function () use ($userId, $xp, $reason, $sourceType, $sourceId, $options) {
            $point = Point::create([
                'user_id' => $userId,
                'source_type' => $sourceType ?? 'system',
                'source_id' => $sourceId,
                'points' => $xp,
                'reason' => $reason,
                'description' => $options['description'] ?? null,
            ]);

            $stats = UserGamificationStat::query()
                ->firstOrNew(['user_id' => $userId]);

            if (! $stats->exists) {
                $stats->fill([
                    'total_xp' => 0,
                    'global_level' => 1,
                    'current_streak' => 0,
                    'longest_streak' => 0,
                ]);
            }

            $stats->total_xp = max(0, (int) $stats->total_xp + $xp);
            $stats->global_level = $this->determineLevelFromXp($stats->total_xp);
            $stats->last_activity_date = Carbon::now();
            $stats->stats_updated_at = Carbon::now();
            $stats->save();

            $this->updateGlobalLeaderboard();

            return $point;
        });
    }

    /**
     * Award a badge to the user.
     */
    public function awardBadge(
        int $userId,
        string $badgeCode,
        string $name,
        string $description = '',
        string $type = 'completion',
        ?int $threshold = null
    ): ?UserBadge {
        $badge = Badge::query()->firstOrCreate(
            ['code' => $badgeCode],
            [
                'name' => $name,
                'description' => $description,
                'type' => $type,
                'threshold' => $threshold,
            ]
        );

        $exists = UserBadge::query()
            ->where('user_id', $userId)
            ->where('badge_id', $badge->id)
            ->exists();

        if ($exists) {
            return null;
        }

        return UserBadge::create([
            'user_id' => $userId,
            'badge_id' => $badge->id,
            'earned_at' => Carbon::now(),
        ]);
    }

    /**
     * Determine global level based on total XP.
     */
    private function determineLevelFromXp(int $totalXp): int
    {
        $level = 1;
        $remainingXp = $totalXp;

        while (true) {
            $required = $this->xpRequiredForLevel($level);

            if ($remainingXp < $required) {
                break;
            }

            $remainingXp -= $required;
            $level++;
        }

        return max(1, $level);
    }

    private function xpRequiredForLevel(int $level): int
    {
        if ($level <= 0) {
            return 0;
        }

        return (int) (100 * pow(1.1, $level - 1));
    }

    public function updateGlobalLeaderboard(): void
    {
        $stats = UserGamificationStat::query()
            ->orderByDesc('total_xp')
            ->orderBy('user_id')
            ->get(['user_id', 'total_xp']);

        foreach ($stats as $index => $stat) {
            Leaderboard::query()->updateOrCreate(
                [
                    'course_id' => null,
                    'user_id' => $stat->user_id,
                ],
                [
                    'rank' => $index + 1,
                ]
            );
        }
    }
}

