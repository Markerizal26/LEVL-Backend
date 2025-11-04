<?php

namespace App\Policies;

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Lesson;

class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('schemes.view');
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $user->can('schemes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('schemes.manage');
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->can('schemes.manage');
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->can('schemes.manage');
    }
}
