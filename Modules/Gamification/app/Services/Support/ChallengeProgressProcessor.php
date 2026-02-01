<?php

declare(strict_types=1);

namespace Modules\Gamification\Services\Support;

use Illuminate\Support\Facades\DB;
use Modules\Gamification\Contracts\Services\GamificationServiceInterface;
use Modules\Gamification\Enums\ChallengeAssignmentStatus;
use Modules\Gamification\Models\UserChallengeAssignment;
use Modules\Gamification\Models\UserChallengeCompletion;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Modules\Gamification\Services\Support\PointManager;
use Modules\Gamification\Services\Support\BadgeManager;

class ChallengeProgressProcessor
{
    public function __construct(
        private readonly PointManager $pointManager,
        private readonly BadgeManager $badgeManager
    ) {}

    public function checkAndUpdateProgress(int $userId, string $criteriaType, int $count = 1): void
    {
        $assignments = UserChallengeAssignment::with('challenge')
            ->where('user_id', $userId)
            ->whereIn('status', [
                ChallengeAssignmentStatus::Pending,
                ChallengeAssignmentStatus::InProgress,
            ])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        foreach ($assignments as $assignment) {
            $challenge = $assignment->challenge;

            if (! $challenge || $challenge->criteria_type !== $criteriaType) {
                continue;
            }

            $assignment->current_progress += $count;

            if ($assignment->status === ChallengeAssignmentStatus::Pending) {
                $assignment->status = ChallengeAssignmentStatus::InProgress;
            }

            if ($assignment->isCriteriaMet()) {
                $this->completeChallenge($assignment);
            } else {
                $assignment->save();
            }
        }
    }

    public function completeChallenge(UserChallengeAssignment $assignment): void
    {
        $assignment->status = ChallengeAssignmentStatus::Completed;
        $assignment->completed_at = now();
        $assignment->save();
    }

    public function claimReward(int $userId, int $challengeId): array
    {
        $assignment = UserChallengeAssignment::with('challenge.badge')
            ->where('user_id', $userId)
            ->where('challenge_id', $challengeId)
            ->first();

        if (! $assignment) {
            throw new NotFoundHttpException(__('messages.challenges.not_found_or_not_assigned'));
        }

        if (! $assignment->isClaimable()) {
            if ($assignment->reward_claimed) {
                throw new BadRequestHttpException(__('messages.challenges.reward_already_claimed'));
            }
            throw new BadRequestHttpException(__('messages.challenges.not_completed_cannot_claim'));
        }

        return DB::transaction(function () use ($assignment) {
            $challenge = $assignment->challenge;
            $rewards = [
                'xp' => 0,
                'badge' => null,
            ];

            if ($challenge->points_reward > 0) {
                $this->pointManager->awardXp(
                    $assignment->user_id,
                    $challenge->points_reward,
                    'bonus',
                    'challenge',
                    $assignment->challenge_id, 
                    [
                        'description' => sprintf('Completed challenge: %s', $challenge->title),
                        'allow_multiple' => false,
                    ]
                );
                $rewards['xp'] = $challenge->points_reward;
            }

            if ($challenge->badge_id && $challenge->badge) {
                $userBadge = $this->badgeManager->awardBadge(
                    $assignment->user_id,
                    $challenge->badge->code,
                    $challenge->badge->name,
                    $challenge->badge->description
                );
                if ($userBadge) {
                    $rewards['badge'] = $challenge->badge;
                }
            }

            $assignment->status = ChallengeAssignmentStatus::Claimed;
            $assignment->reward_claimed = true;
            $assignment->save();

            UserChallengeCompletion::create([
                'user_id' => $assignment->user_id,
                'challenge_id' => $challenge->id,
                'completed_date' => now()->toDateString(),
                'xp_earned' => $rewards['xp'],
                'completion_data' => [
                    'progress' => $assignment->current_progress,
                    'target' => $challenge->criteria_target,
                ],
            ]);

            return $rewards;
        });
    }
}
