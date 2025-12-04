<?php

namespace Modules\Gamification\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Gamification\Enums\ChallengeAssignmentStatus;
use Modules\Gamification\Models\Challenge;
use Modules\Gamification\Models\UserChallengeAssignment;
use Modules\Gamification\Models\UserChallengeCompletion;

class ChallengeService
{
    public function __construct(
        private readonly GamificationService $gamificationService
    ) {}

    /**
     * Get all active challenges assigned to a user.
     */
    public function getUserChallenges(int $userId): Collection
    {
        return UserChallengeAssignment::with('challenge.badge')
            ->where('user_id', $userId)
            ->whereIn('status', [
                ChallengeAssignmentStatus::Pending,
                ChallengeAssignmentStatus::InProgress,
                ChallengeAssignmentStatus::Completed,
            ])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * Get a specific active challenge.
     */
    public function getActiveChallenge(int $challengeId): ?Challenge
    {
        return Challenge::active()->find($challengeId);
    }

    /**
     * Get user's completed challenges.
     */
    public function getCompletedChallenges(int $userId, int $limit = 15): Collection
    {
        return UserChallengeCompletion::with('challenge.badge')
            ->where('user_id', $userId)
            ->orderByDesc('completed_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Assign daily challenges to all active users.
     */
    public function assignDailyChallenges(): int
    {
        $dailyChallenges = Challenge::daily()->active()->get();

        if ($dailyChallenges->isEmpty()) {
            return 0;
        }

        $activeUserIds = $this->getActiveUserIds();
        $assignedCount = 0;
        $today = Carbon::today();
        $endOfDay = Carbon::today()->endOfDay();

        foreach ($activeUserIds as $userId) {
            foreach ($dailyChallenges as $challenge) {
                if ($this->hasActiveAssignment($userId, $challenge->id, 'daily')) {
                    continue;
                }

                UserChallengeAssignment::create([
                    'user_id' => $userId,
                    'challenge_id' => $challenge->id,
                    'assigned_date' => $today,
                    'status' => ChallengeAssignmentStatus::Pending,
                    'current_progress' => 0,
                    'expires_at' => $endOfDay,
                ]);

                $assignedCount++;
            }
        }

        return $assignedCount;
    }

    /**
     * Assign weekly challenges to all active users.
     */
    public function assignWeeklyChallenges(): int
    {
        $weeklyChallenges = Challenge::weekly()->active()->get();

        if ($weeklyChallenges->isEmpty()) {
            return 0;
        }

        $activeUserIds = $this->getActiveUserIds();
        $assignedCount = 0;
        $today = Carbon::today();
        $endOfWeek = Carbon::now()->endOfWeek();

        foreach ($activeUserIds as $userId) {
            foreach ($weeklyChallenges as $challenge) {
                if ($this->hasActiveAssignment($userId, $challenge->id, 'weekly')) {
                    continue;
                }

                UserChallengeAssignment::create([
                    'user_id' => $userId,
                    'challenge_id' => $challenge->id,
                    'assigned_date' => $today,
                    'status' => ChallengeAssignmentStatus::Pending,
                    'current_progress' => 0,
                    'expires_at' => $endOfWeek,
                ]);

                $assignedCount++;
            }
        }

        return $assignedCount;
    }

    /**
     * Check and update challenge progress for a user.
     */
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

            // Update progress
            $assignment->current_progress += $count;

            if ($assignment->status === ChallengeAssignmentStatus::Pending) {
                $assignment->status = ChallengeAssignmentStatus::InProgress;
            }

            // Check if completed
            if ($assignment->isCriteriaMet()) {
                $this->completeChallenge($assignment);
            } else {
                $assignment->save();
            }
        }
    }

    /**
     * Mark a challenge as completed.
     */
    public function completeChallenge(UserChallengeAssignment $assignment): void
    {
        $assignment->status = ChallengeAssignmentStatus::Completed;
        $assignment->completed_at = now();
        $assignment->save();
    }

    /**
     * Claim reward for a completed challenge.
     */
    public function claimReward(int $userId, int $challengeId): array
    {
        $assignment = UserChallengeAssignment::with('challenge.badge')
            ->where('user_id', $userId)
            ->where('challenge_id', $challengeId)
            ->first();

        if (! $assignment) {
            throw new \Exception('Challenge tidak ditemukan atau belum di-assign.');
        }

        if (! $assignment->isClaimable()) {
            if ($assignment->reward_claimed) {
                throw new \Exception('Reward sudah di-claim sebelumnya.');
            }
            throw new \Exception('Challenge belum selesai, tidak dapat claim reward.');
        }

        return DB::transaction(function () use ($assignment) {
            $challenge = $assignment->challenge;
            $rewards = [
                'xp' => 0,
                'badge' => null,
            ];

            // Award XP
            if ($challenge->points_reward > 0) {
                $this->gamificationService->awardXp(
                    $assignment->user_id,
                    $challenge->points_reward,
                    'bonus',
                    'challenge',
                    $challenge->id,
                    [
                        'description' => sprintf('Completed challenge: %s', $challenge->title),
                        'allow_multiple' => false,
                    ]
                );
                $rewards['xp'] = $challenge->points_reward;
            }

            // Award badge if exists
            if ($challenge->badge_id && $challenge->badge) {
                $userBadge = $this->gamificationService->awardBadge(
                    $assignment->user_id,
                    $challenge->badge->code,
                    $challenge->badge->name,
                    $challenge->badge->description
                );
                if ($userBadge) {
                    $rewards['badge'] = $challenge->badge;
                }
            }

            // Mark as claimed
            $assignment->status = ChallengeAssignmentStatus::Claimed;
            $assignment->reward_claimed = true;
            $assignment->save();

            // Create completion record
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

    /**
     * Expire overdue challenges.
     */
    public function expireOverdueChallenges(): int
    {
        return UserChallengeAssignment::whereIn('status', [
            ChallengeAssignmentStatus::Pending,
            ChallengeAssignmentStatus::InProgress,
        ])
            ->where('expires_at', '<', now())
            ->update(['status' => ChallengeAssignmentStatus::Expired]);
    }

    /**
     * Get active user IDs (users with at least one enrollment).
     */
    private function getActiveUserIds(): array
    {
        return \Modules\Enrollments\Models\Enrollment::query()
            ->whereIn('status', [
                EnrollmentStatus::Active->value,
                EnrollmentStatus::Pending->value,
            ])
            ->distinct()
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Check if user already has an active assignment for a challenge type.
     */
    private function hasActiveAssignment(int $userId, int $challengeId, string $challengeType): bool
    {
        $query = UserChallengeAssignment::where('user_id', $userId)
            ->whereIn('status', [
                ChallengeAssignmentStatus::Pending,
                ChallengeAssignmentStatus::InProgress,
            ])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        // For daily challenges, check if assigned today
        if ($challengeType === 'daily') {
            $query->whereDate('assigned_date', Carbon::today());
        }

        // For weekly challenges, check if assigned this week
        if ($challengeType === 'weekly') {
            $query->whereBetween('assigned_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]);
        }

        return $query->where('challenge_id', $challengeId)->exists();
    }
}
