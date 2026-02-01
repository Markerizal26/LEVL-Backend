<?php

namespace Modules\Gamification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Gamification\Models\Challenge;
use Modules\Gamification\Services\ChallengeService;
use Modules\Gamification\Transformers\ChallengeCompletionResource;
use Modules\Gamification\Transformers\ChallengeResource;
use Modules\Gamification\Transformers\UserChallengeAssignmentResource;

class ChallengeController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ChallengeService $challengeService) {}

    public function index(Request $request): JsonResponse
    {
        $challenges = Challenge::active()
            ->with("badge")
            ->orderBy("type")
            ->orderBy("points_reward", "desc")
            ->paginate($request->input("per_page", 15));

        $userId = $request->user()?->id;

        if ($userId) {
            $userChallenges = $this->challengeService->getUserChallenges($userId)->keyBy("challenge_id");

            
            $challenges->getCollection()->transform(function ($challenge) use ($userChallenges) {
                $challenge->user_progress = $userChallenges->get($challenge->id)
                    ? [
                        "current" => $userChallenges->get($challenge->id)->current_progress,
                        "target" => $challenge->criteria_target,
                        "percentage" => $userChallenges->get($challenge->id)->getProgressPercentage(),
                        "status" => $userChallenges->get($challenge->id)->status->value,
                        "expires_at" => $userChallenges->get($challenge->id)->expires_at,
                    ]
                    : null;
                return $challenge;
            });
        }

        return $this->paginateResponse($challenges, null, ChallengeResource::class);
    }

    public function show(int $challengeId, Request $request): JsonResponse
    {
        
        
        
        
        
        
        
        
        $challenge = $this->challengeService->getActiveChallenge($challengeId);

        if (!$challenge) {
            return $this->notFound(__("messages.challenges.not_found"));
        }

        $userId = $request->user()?->id;

        if ($userId) {
             $userChallenges = $this->challengeService->getUserChallenges($userId)->keyBy("challenge_id");
             $assignment = $userChallenges->get($challenge->id);
             
             if ($assignment) {
                 $challenge->user_progress = [
                    "current" => $assignment->current_progress,
                    "target" => $challenge->criteria_target,
                    "percentage" => $assignment->getProgressPercentage(),
                    "status" => $assignment->status->value,
                    "expires_at" => $assignment->expires_at,
                    "is_claimable" => $assignment->isClaimable(),
                 ];
             }
        }

        return $this->success(new ChallengeResource($challenge), __("messages.challenges.retrieved"));
    }

    public function myChallenges(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $challenges = $this->challengeService->getUserChallenges($userId);

        return $this->success(UserChallengeAssignmentResource::collection($challenges));
    }

    public function completed(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $limit = $request->input("limit", 15);

        $completions = $this->challengeService->getCompletedChallenges($userId, $limit);

        return $this->success(
            ["completions" => ChallengeCompletionResource::collection($completions)],
            __("messages.challenges.completions_retrieved"),
        );
    }
  
    public function claim(int $challengeId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $rewards = $this->challengeService->claimReward($userId, $challengeId);

        return $this->success([
            "message" => __("messages.challenges.reward_claimed"),
            "rewards" => $rewards, 
        ]);
    }
}
