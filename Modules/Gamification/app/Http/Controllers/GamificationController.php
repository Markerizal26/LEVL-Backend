<?php

namespace Modules\Gamification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Gamification\Contracts\Services\GamificationServiceInterface;
use Modules\Gamification\Transformers\PointResource;
use Modules\Gamification\Transformers\UserBadgeResource;

class GamificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GamificationServiceInterface $gamificationService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $summary = $this->gamificationService->getSummary($userId);

        return $this->success($summary, __('gamification.summary_retrieved'));
    }

    public function badges(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $badges = $this->gamificationService->getUserBadges($userId);

        return $this->success([
            'badges' => UserBadgeResource::collection($badges)
        ], __('gamification.badges_retrieved'));
    }

    public function pointsHistory(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->input('per_page', 15);

        $points = $this->gamificationService->getPointsHistory($userId, $perPage);

        return $this->paginateResponse($points, __('gamification.points_history_retrieved'), PointResource::class);
    }

    public function achievements(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data = $this->gamificationService->getAchievements($userId);

        return $this->success($data, __('gamification.achievements_retrieved'));
    }

    public function unitLevels(Request $request, int $courseId): JsonResponse
    {
        $userId = $request->user()->id;
        $data = $this->gamificationService->getUnitLevels($userId, $courseId);

        return $this->success(['unit_levels' => $data], __('gamification.levels_retrieved'));
    }
}
