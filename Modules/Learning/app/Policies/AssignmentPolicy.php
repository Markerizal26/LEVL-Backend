<?php

declare(strict_types=1);

namespace Modules\Learning\Policies;

use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;

class AssignmentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Assignment $assignment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin') || $user->hasRole('Admin') || $user->hasRole('Instructor');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        return $user->hasRole('Instructor') && $assignment->lesson?->unit?->course?->instructor_id === $user->id;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $user->hasRole('Admin') || ($user->hasRole('Instructor') && $assignment->lesson?->unit?->course?->instructor_id === $user->id);
    }

    public function publish(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    /**
     * Determine if the user can grant overrides for the assignment.
     *
     * Requirements: 24.1, 24.2, 24.3
     */
    public function grantOverride(User $user, Assignment $assignment): bool
    {
        // Only instructors, admins, and superadmins can grant overrides
        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        // Instructors can only grant overrides for their own courses
        return $user->hasRole('Instructor') && $assignment->lesson?->unit?->course?->instructor_id === $user->id;
    }

    /**
     * Determine if the user can view overrides for the assignment.
     */
    public function viewOverrides(User $user, Assignment $assignment): bool
    {
        return $this->grantOverride($user, $assignment);
    }

    /**
     * Determine if the user can duplicate the assignment.
     *
     * Requirement: 25.1
     */
    public function duplicate(User $user, Assignment $assignment): bool
    {
        // Any instructor, admin, or superadmin can duplicate assignments
        return $user->hasRole('Superadmin') || $user->hasRole('Admin') || $user->hasRole('Instructor');
    }
}
