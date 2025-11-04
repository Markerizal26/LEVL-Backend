<?php

namespace App\Policies;

use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('learning.tasks.view');
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->can('learning.tasks.view');
    }

    public function create(User $user): bool
    {
        return $user->can('learning.tasks.create');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->can('learning.tasks.edit');
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->can('learning.tasks.delete');
    }

    public function submit(User $user, Assignment $assignment): bool
    {
        return $user->can('learning.tasks.submit');
    }
}
