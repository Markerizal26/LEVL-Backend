<?php

declare(strict_types=1);

namespace Modules\Gamification\Services\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Models\Enrollment;
use Modules\Gamification\Enums\ChallengeAssignmentStatus;
use Modules\Gamification\Models\Challenge;
use Modules\Gamification\Models\UserChallengeAssignment;
use Modules\Gamification\Models\UserChallengeCompletion;

class ChallengeFinder
{
    
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

    public function getActiveChallenge(int $challengeId): ?Challenge
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "challenge_active_{$challengeId}",
            now()->addMinutes(10), 
            fn () => Challenge::active()->find($challengeId)
        );
    }

    public function getCompletedChallenges(int $userId, int $limit = 15): Collection
    {
        return UserChallengeCompletion::with('challenge.badge')
            ->where('user_id', $userId)
            ->orderByDesc('completed_date')
            ->limit($limit)
            ->get();
    }

    public function getActiveUsersQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Enrollment::query()
            ->whereIn('status', [
                EnrollmentStatus::Active->value,
                EnrollmentStatus::Pending->value,
            ])
            ->select('user_id') 
            ->distinct();
    }

    public function hasActiveAssignment(int $userId, int $challengeId, string $challengeType): bool
    {
        
        $query = UserChallengeAssignment::where('user_id', $userId)
            ->whereIn('status', [
                ChallengeAssignmentStatus::Pending,
                ChallengeAssignmentStatus::InProgress,
                ChallengeAssignmentStatus::Completed,
            ])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($challengeType === 'daily') {
            $query->whereDate('assigned_date', Carbon::today());
        }

        if ($challengeType === 'weekly') {
            $query->whereBetween('assigned_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]);
        }

        return $query->where('challenge_id', $challengeId)->exists();
    }
}
