<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Contracts\Services\ProfileStatisticsServiceInterface;
use Modules\Auth\Models\User;

class ProfileStatisticsService implements ProfileStatisticsServiceInterface
{
    public function getStatistics(User $user): array
    {
        return Cache::remember("user_statistics_{$user->id}", 300, function () use ($user) {
            return [
                'enrollments' => $this->getEnrollmentStats($user),
                'gamification' => $this->getGamificationStats($user),
                'performance' => $this->getPerformanceStats($user),
                'activity' => $this->getActivityStats($user),
            ];
        });
    }

    public function getEnrollmentStats(User $user): array
    {
        $enrollments = DB::table('enrollments')
            ->where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total_enrolled,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as total_completed,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress
            ')
            ->first();

        return [
            'total_enrolled' => $enrollments->total_enrolled ?? 0,
            'total_completed' => $enrollments->total_completed ?? 0,
            'in_progress' => $enrollments->in_progress ?? 0,
        ];
    }

    public function getGamificationStats(User $user): array
    {
        $stats = DB::table('user_gamification_stats')
            ->where('user_id', $user->id)
            ->first();

        $badgesCount = DB::table('user_badges')
            ->where('user_id', $user->id)
            ->count();

        $streak = DB::table('learning_streaks')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'total_points' => $stats->total_points ?? 0,
            'current_level' => $stats->current_level ?? 1,
            'badges_earned' => $badgesCount,
            'learning_streak' => $streak->current_streak ?? 0,
        ];
    }

    public function getPerformanceStats(User $user): array
    {
        $completionRate = $this->calculateCompletionRate($user);
        $averageScore = $this->calculateAverageScore($user);

        return [
            'completion_rate' => $completionRate,
            'average_score' => $averageScore,
        ];
    }

    public function getActivityStats(User $user): array
    {
        $recentActivities = DB::table('user_activities')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'activities_last_30_days' => $recentActivities,
        ];
    }

    public function calculateCompletionRate(User $user): float
    {
        $stats = $this->getEnrollmentStats($user);

        if ($stats['total_enrolled'] === 0) {
            return 0.0;
        }

        return round(($stats['total_completed'] / $stats['total_enrolled']) * 100, 2);
    }

    public function calculateAverageScore(User $user): float
    {
        $averageScore = DB::table('attempts')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->avg('score');

        return round($averageScore ?? 0, 2);
    }

    public function getTotalPoints(User $user): int
    {
        $stats = DB::table('user_gamification_stats')
            ->where('user_id', $user->id)
            ->first();

        return $stats->total_points ?? 0;
    }

    public function getCurrentLevel(User $user): int
    {
        $stats = DB::table('user_gamification_stats')
            ->where('user_id', $user->id)
            ->first();

        return $stats->current_level ?? 1;
    }
}
