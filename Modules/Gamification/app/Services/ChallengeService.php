<?php

declare(strict_types=1);

namespace Modules\Gamification\Services;

use Illuminate\Support\Collection;
use Modules\Gamification\Contracts\Services\ChallengeServiceInterface;
use Modules\Gamification\Models\Challenge;
use Modules\Gamification\Models\UserChallengeAssignment;
use Modules\Gamification\Services\Support\ChallengeAssignmentProcessor;
use Modules\Gamification\Services\Support\ChallengeFinder;
use Modules\Gamification\Services\Support\ChallengeProgressProcessor;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class ChallengeService implements ChallengeServiceInterface
{
    public function __construct(
        private readonly ChallengeFinder $finder,
        private readonly ChallengeAssignmentProcessor $assignmentProcessor,
        private readonly ChallengeProgressProcessor $progressProcessor
    ) {}

    public function getChallengesQuery(?int $userId = null): QueryBuilder
    {
        return QueryBuilder::for(Challenge::class)
            ->active()
            ->with("badge")
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('points_reward'),
                AllowedFilter::callback('has_progress', function ($query, $value) use ($userId) {
                    if (!$userId) return;
                    
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                        // User has progress (assignment exists)
                        $query->whereHas('userAssignments', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                    } else {
                        // User has no progress (no assignment)
                        $query->whereDoesntHave('userAssignments', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                    }
                }),
                AllowedFilter::callback('criteria_type', function ($query, $value) {
                    $query->where('criteria->type', $value);
                }),
            ])
            ->allowedSorts(['points_reward', 'created_at', 'type'])
            ->defaultSort('-points_reward');
    }

    public function getUserChallenges(int $userId): Collection
    {
        return $this->finder->getUserChallenges($userId);
    }

    public function getUserChallengesPaginated(int $userId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->finder->getUserChallengesPaginated($userId, $perPage);
    }

    public function getActiveChallenge(int $challengeId): ?Challenge
    {
        return $this->finder->getActiveChallenge($challengeId);
    }

    public function getCompletedChallengesQuery(int $userId): QueryBuilder
    {
        return QueryBuilder::for(\Modules\Gamification\Models\UserChallengeCompletion::class)
            ->with('challenge.badge')
            ->where('user_id', $userId)
            ->allowedFilters([
                AllowedFilter::partial('challenge.title'),
                AllowedFilter::exact('challenge.type'),
                AllowedFilter::callback('search', function ($query, $value) {
                        $query->whereHas('challenge', function ($q) use ($value) {
                             $q->search($value);
                        });
                }),
            ])
            ->allowedSorts(['completed_date', 'points_earned'])
            ->defaultSort('-completed_date');
    }

    public function getCompletedChallenges(int $userId, int $limit = 15): Collection
    {
        return $this->finder->getCompletedChallenges($userId, $limit);
    }

    public function assignDailyChallenges(): int
    {
        return $this->assignmentProcessor->assignDailyChallenges();
    }

    public function assignWeeklyChallenges(): int
    {
        return $this->assignmentProcessor->assignWeeklyChallenges();
    }

    public function checkAndUpdateProgress(int $userId, string $criteriaType, int $count = 1): void
    {
        $this->progressProcessor->checkAndUpdateProgress($userId, $criteriaType, $count);
    }

    public function completeChallenge(UserChallengeAssignment $assignment): void
    {
        $this->progressProcessor->completeChallenge($assignment);
    }

    public function claimReward(int $userId, int $challengeId): array
    {
        return $this->progressProcessor->claimReward($userId, $challengeId);
    }

    public function expireOverdueChallenges(): int
    {
        return $this->assignmentProcessor->expireOverdueChallenges();
    }
}
