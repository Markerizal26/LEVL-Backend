<?php

namespace Modules\Auth\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Auth\Contracts\Services\UserActivityServiceInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

class UserActivityService implements UserActivityServiceInterface
{
    public function logActivity(User $user, string $type, array $data, ?Model $related = null): UserActivity
    {
        return UserActivity::create([
            'user_id' => $user->id,
            'activity_type' => $type,
            'activity_data' => $data,
            'related_type' => $related ? get_class($related) : null,
            'related_id' => $related?->id,
        ]);
    }

    public function getActivities(User $user, array $filters = []): Collection
    {
        $query = UserActivity::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if (! empty($filters['type'])) {
            $query->where('activity_type', $filters['type']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    public function getRecentActivities(User $user, int $limit = 10): Collection
    {
        return UserActivity::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function logEnrollment(User $user, $course): void
    {
        $this->logActivity($user, UserActivity::TYPE_ENROLLMENT, [
            'course_id' => $course->id,
            'course_name' => $course->name,
        ], $course);
    }

    public function logCompletion(User $user, $course): void
    {
        $this->logActivity($user, UserActivity::TYPE_COMPLETION, [
            'course_id' => $course->id,
            'course_name' => $course->name,
        ], $course);
    }

    public function logSubmission(User $user, $assignment): void
    {
        $this->logActivity($user, UserActivity::TYPE_SUBMISSION, [
            'assignment_id' => $assignment->id,
            'assignment_name' => $assignment->name ?? 'Assignment',
        ], $assignment);
    }

    public function logAchievement(User $user, $badge): void
    {
        $this->logActivity($user, UserActivity::TYPE_BADGE_EARNED, [
            'badge_id' => $badge->id,
            'badge_name' => $badge->name,
        ], $badge);
    }
}
