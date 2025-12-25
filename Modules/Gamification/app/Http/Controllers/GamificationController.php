<?php

namespace Modules\Gamification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Gamification\Contracts\Repositories\PointRepositoryInterface;
use Modules\Gamification\Contracts\Repositories\UserBadgeRepositoryInterface;
use Modules\Gamification\Contracts\Repositories\UserGamificationStatRepositoryInterface;
use Modules\Gamification\Services\ChallengeService;
use Modules\Gamification\Services\GamificationService;
use Modules\Gamification\Services\LeaderboardService;

/**
 * @tags Gamifikasi
 */
class GamificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GamificationService $gamificationService,
        private readonly LeaderboardService $leaderboardService,
        private readonly ChallengeService $challengeService,
        private readonly UserGamificationStatRepositoryInterface $statRepository,
        private readonly UserBadgeRepositoryInterface $badgeRepository,
        private readonly PointRepositoryInterface $pointRepository
    ) {}

    /**
     * Mengambil ringkasan gamifikasi user
     *
     * Mengambil ringkasan lengkap gamifikasi user termasuk total XP, level, streak, jumlah badge, dan ranking di leaderboard.
     *
     *
     * @summary Mengambil ringkasan gamifikasi user
     *
     * @response 200 scenario="Success" {"success": true, "data": {"total_xp": 1500, "level": 5, "xp_to_next_level": 200, "progress_to_next_level": 75, "badges_count": 3, "current_streak": 7, "longest_streak": 14, "rank": 25, "active_challenges": 2}}
     *
     * @authenticated
     */
    public function summary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = $this->statRepository->findByUserId($userId);
        $rankData = $this->leaderboardService->getUserRank($userId);
        $activeChallenges = $this->challengeService->getUserChallenges($userId)->count();
        $badgesCount = $this->badgeRepository->countByUserId($userId);

        return $this->success([
            'total_xp' => $stats?->total_xp ?? 0,
            'level' => $stats?->global_level ?? 0,
            'xp_to_next_level' => $stats?->xp_to_next_level ?? 100,
            'progress_to_next_level' => $stats?->progress_to_next_level ?? 0,
            'badges_count' => $badgesCount,
            'current_streak' => $stats?->current_streak ?? 0,
            'longest_streak' => $stats?->longest_streak ?? 0,
            'rank' => $rankData['rank'],
            'active_challenges' => $activeChallenges,
        ], __('gamification.summary_retrieved'));
    }

    /**
     * Mengambil daftar badge user
     *
     * Mengambil semua badge yang telah diperoleh user, diurutkan berdasarkan waktu perolehan terbaru.
     *
     *
     * @summary Mengambil daftar badge user
     *
     * @response 200 scenario="Success" {"success": true, "data": {"badges": [{"id": 1, "code": "first_login", "name": "Pemula", "description": "Login pertama kali", "icon_url": "https://example.com/badges/first_login.png", "type": "achievement", "awarded_at": "2024-01-15T10:00:00Z"}]}}
     *
     * @authenticated
     */
    public function badges(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $badges = $this->badgeRepository->findByUserId($userId)
            ->map(function ($userBadge) {
                return [
                    'id' => $userBadge->badge_id,
                    'code' => $userBadge->badge?->code,
                    'name' => $userBadge->badge?->name,
                    'description' => $userBadge->description ?? $userBadge->badge?->description,
                    'icon_url' => $userBadge->badge?->icon_url,
                    'type' => $userBadge->badge?->type?->value,
                    'awarded_at' => $userBadge->awarded_at,
                ];
            });

        return $this->success(['badges' => $badges], __('gamification.badges_retrieved'));
    }

    /**
     * Mengambil riwayat XP user
     *
     * Mengambil riwayat perolehan XP user dengan pagination. Menampilkan sumber XP, alasan, dan deskripsi.
     *
     *
     * @summary Mengambil riwayat XP user
     *
     * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
     *
     * @response 200 scenario="Success" {"success": true, "data": {"points": [{"id": 1, "points": 50, "source_type": "lesson_complete", "source_type_label": "Menyelesaikan Pelajaran", "reason": "completion", "reason_label": "Penyelesaian", "description": "Menyelesaikan pelajaran Introduction to Laravel", "created_at": "2024-01-15T10:00:00Z"}], "meta": {"current_page": 1, "per_page": 15, "total": 100, "last_page": 7}}}
     *
     * @authenticated
     */
    public function pointsHistory(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->input('per_page', 15);

        $points = $this->pointRepository->paginateByUserId($userId, $perPage);

        $data = $points->getCollection()->map(function ($point) {
            return [
                'id' => $point->id,
                'points' => $point->points,
                'source_type' => $point->source_type?->value,
                'source_type_label' => $point->source_type?->label(),
                'reason' => $point->reason?->value,
                'reason_label' => $point->reason?->label(),
                'description' => $point->description,
                'created_at' => $point->created_at,
            ];
        });

        return $this->success([
            'points' => $data,
            'meta' => [
                'current_page' => $points->currentPage(),
                'per_page' => $points->perPage(),
                'total' => $points->total(),
                'last_page' => $points->lastPage(),
            ],
        ], __('gamification.points_history_retrieved'));
    }

    /**
     * Mengambil pencapaian user
     *
     * Mengambil daftar milestone/pencapaian user beserta progress menuju milestone berikutnya. Milestone berdasarkan total XP yang dikumpulkan.
     *
     *
     * @summary Mengambil pencapaian user
     *
     * @response 200 scenario="Success" {"success": true, "data": {"achievements": [{"name": "Pemula", "xp_required": 100, "level_required": 1, "achieved": true, "progress": 100}, {"name": "Pelajar Aktif", "xp_required": 500, "level_required": 5, "achieved": false, "progress": 60}], "next_milestone": {"name": "Pelajar Aktif", "xp_required": 500, "level_required": 5, "achieved": false, "progress": 60}, "current_xp": 300, "current_level": 3}}
     *
     * @authenticated
     */
    public function achievements(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = $this->statRepository->findByUserId($userId);
        $totalXp = $stats?->total_xp ?? 0;
        $level = $stats?->global_level ?? 0;

        // Get milestones from database
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

        // Find next milestone
        $nextMilestone = $achievements->first(fn ($m) => ! $m['achieved']);

        return $this->success([
            'achievements' => $achievements,
            'next_milestone' => $nextMilestone,
            'current_xp' => $totalXp,
            'current_level' => $level,
        ], __('gamification.achievements_retrieved'));
    }
}
