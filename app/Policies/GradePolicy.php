<?php

namespace App\Policies;

use Modules\Auth\Models\User;
use Modules\Grading\Entities\Grade;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('grading.view');
    }

    public function view(User $user, Grade $grade): bool
    {
        return $user->can('grading.view');
    }

    public function create(User $user): bool
    {
        return $user->can('grading.tasks.score') || $user->can('grading.quizzes.score');
    }

    public function update(User $user, Grade $grade): bool
    {
        return $user->can('grading.tasks.score') || $user->can('grading.quizzes.score');
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $user->can('grading.view');
    }
}


