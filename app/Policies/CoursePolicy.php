<?php

namespace App\Policies;

use Modules\Auth\Models\User;
use Modules\Schemes\Entities\Course;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('schemes.view');
    }

    public function view(User $user, Course $course): bool
    {
        return $user->can('schemes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('schemes.manage');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->can('schemes.manage');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->can('schemes.manage');
    }
}


