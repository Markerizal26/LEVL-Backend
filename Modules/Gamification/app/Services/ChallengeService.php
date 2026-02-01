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

class ChallengeService implements ChallengeServiceInterface
{
    public function __construct(
        private readonly ChallengeFinder $finder,
        private readonly ChallengeAssignmentProcessor $assignmentProcessor,
        private readonly ChallengeProgressProcessor $progressProcessor
    ) {}

    public function getUserChallenges(int $userId): Collection
    {
        return $this->finder->getUserChallenges($userId);
    }

    public function getActiveChallenge(int $challengeId): ?Challenge
    {
        return $this->finder->getActiveChallenge($challengeId);
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
