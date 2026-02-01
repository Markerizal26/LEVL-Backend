<?php

namespace Modules\Gamification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Gamification\Services\LeaderboardService;
use Modules\Gamification\Transformers\LeaderboardResource;

class LeaderboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LeaderboardService $leaderboardService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->input('per_page', 10), 100);
        $page = $request->input('page', 1);

        $leaderboard = $this->leaderboardService->getGlobalLeaderboard($perPage, $page);

        // We need to attach rank manually because offset depends on page
        $leaderboard->getCollection()->transform(function ($stat, $index) use ($leaderboard) {
            $rank = ($leaderboard->currentPage() - 1) * $leaderboard->perPage() + $index + 1;
            // Attach temporary rank property for Resource to use
            $stat->rank = $rank; 
            return $stat;
        });

        return $this->paginateResponse($leaderboard, __('gamification.leaderboard_retrieved'), LeaderboardResource::class);
    }

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
