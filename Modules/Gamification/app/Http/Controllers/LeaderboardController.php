<?php

namespace Modules\Gamification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Gamification\Services\LeaderboardService;

/**
 * @tags Gamifikasi
 */
class LeaderboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LeaderboardService $leaderboardService
    ) {}

    /**
     * Get global leaderboard.
     *
     * @summary Mengambil leaderboard global
     *
     * @description Mengambil daftar peringkat user berdasarkan total XP dengan pagination. Maksimal 100 item per halaman.
     *
     * @queryParam per_page integer Jumlah item per halaman (max 100). Default: 10. Example: 10
     * @queryParam page integer Nomor halaman. Default: 1. Example: 1
     *
     * @allowedSorts total_xp, global_level
     *
     * @response 200 {"success": true, "data": {"leaderboard": [{"rank": 1, "user": {"id": 1, "name": "John Doe", "avatar_url": "https://example.com/avatar.jpg"}, "total_xp": 5000, "level": 15}], "meta": {"current_page": 1, "per_page": 10, "total": 100, "last_page": 10}}}
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->input('per_page', 10), 100);
        $page = $request->input('page', 1);

        $leaderboard = $this->leaderboardService->getGlobalLeaderboard($perPage, $page);

        // Transform data
        $data = $leaderboard->getCollection()->map(function ($stat, $index) use ($leaderboard) {
            $rank = ($leaderboard->currentPage() - 1) * $leaderboard->perPage() + $index + 1;

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
        });

        return $this->success([
            'leaderboard' => $data,
            'meta' => [
                'current_page' => $leaderboard->currentPage(),
                'per_page' => $leaderboard->perPage(),
                'total' => $leaderboard->total(),
                'last_page' => $leaderboard->lastPage(),
            ],
        ], __('gamification.leaderboard_retrieved'));
    }

    /**
     * Get current user's rank.
     *
     * @summary Mengambil ranking user saat ini
     *
     * @description Mengambil peringkat user yang sedang login beserta user di sekitarnya (surrounding).
     *
     * @response 200 {"success": true, "data": {"rank": 25, "total_xp": 1500, "level": 5, "surrounding": [{"rank": 24, "user": {"id": 10, "name": "Jane"}, "total_xp": 1550}, {"rank": 25, "user": {"id": 1, "name": "You"}, "total_xp": 1500}, {"rank": 26, "user": {"id": 15, "name": "Bob"}, "total_xp": 1450}]}}
     */
    public function myRank(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $rankData = $this->leaderboardService->getUserRank($userId);

        return $this->success([
            'rank' => $rankData['rank'],
            'total_xp' => $rankData['total_xp'],
            'level' => $rankData['level'],
            'surrounding' => $rankData['surrounding'],
        ], __('gamification.rank_retrieved'));
    }
}
