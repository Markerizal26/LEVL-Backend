<?php

declare(strict_types=1);

namespace Modules\Gamification\Services\Support;

use Carbon\Carbon;
use Modules\Gamification\Enums\ChallengeAssignmentStatus;
use Modules\Gamification\Models\Challenge;
use Modules\Gamification\Models\UserChallengeAssignment;

class ChallengeAssignmentProcessor
{
    public function __construct(
        private readonly ChallengeFinder $finder
    ) {}

    public function assignDailyChallenges(): int
    {
        $dailyChallenges = Challenge::daily()->active()->get();

        if ($dailyChallenges->isEmpty()) {
            return 0;
        }

        $assignedCount = 0;
        $today = Carbon::today();
        $endOfDay = Carbon::today()->endOfDay();

        $this->finder->getActiveUsersQuery()->chunkById(100, function ($enrollments) use ($dailyChallenges, $today, $endOfDay, &$assignedCount) {
            foreach ($enrollments as $enrollment) {
                $userId = $enrollment->user_id;

                foreach ($dailyChallenges as $challenge) {
                    if ($this->finder->hasActiveAssignment($userId, $challenge->id, 'daily')) {
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
        }, 'id'); 

        return $assignedCount;
    }

    public function assignWeeklyChallenges(): int
    {
        $weeklyChallenges = Challenge::weekly()->active()->get();

        if ($weeklyChallenges->isEmpty()) {
            return 0;
        }

        $assignedCount = 0;
        $today = Carbon::today();
        $endOfWeek = Carbon::now()->endOfWeek();

        $this->finder->getActiveUsersQuery()->chunkById(100, function ($enrollments) use ($weeklyChallenges, $today, $endOfWeek, &$assignedCount) {
            foreach ($enrollments as $enrollment) {
                $userId = $enrollment->user_id;

                foreach ($weeklyChallenges as $challenge) {
                    if ($this->finder->hasActiveAssignment($userId, $challenge->id, 'weekly')) {
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
        }, 'id');

        return $assignedCount;
    }

    public function expireOverdueChallenges(): int
    {
        return UserChallengeAssignment::whereIn('status', [
            ChallengeAssignmentStatus::Pending,
            ChallengeAssignmentStatus::InProgress,
        ])
            ->where('expires_at', '<', now())
            ->update(['status' => ChallengeAssignmentStatus::Expired]);
    }
}
