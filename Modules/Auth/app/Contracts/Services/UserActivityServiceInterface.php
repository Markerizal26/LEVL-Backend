<?php

namespace Modules\Auth\Contracts\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

interface UserActivityServiceInterface
{
    public function logActivity(User $user, string $type, array $data, ?Model $related = null): UserActivity;

    public function getActivities(User $user, array $filters = []): Collection;

    public function getRecentActivities(User $user, int $limit = 10): Collection;

    public function logEnrollment(User $user, $course): void;

    public function logCompletion(User $user, $course): void;

    public function logSubmission(User $user, $assignment): void;

    public function logAchievement(User $user, $badge): void;
}
