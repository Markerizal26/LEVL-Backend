<?php

namespace Modules\Auth\Contracts\Services;

use Modules\Auth\Models\User;

interface ProfileStatisticsServiceInterface
{
    public function getStatistics(User $user): array;

    public function getEnrollmentStats(User $user): array;

    public function getGamificationStats(User $user): array;

    public function getPerformanceStats(User $user): array;

    public function getActivityStats(User $user): array;

    public function calculateCompletionRate(User $user): float;

    public function calculateAverageScore(User $user): float;

    public function getTotalPoints(User $user): int;

    public function getCurrentLevel(User $user): int;
}
