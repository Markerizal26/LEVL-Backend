<?php

namespace Modules\Enrollments\Policies;

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;

class EnrollmentPolicy
{
    /**
     * Determine if the user can modify the enrollment
     */
    public function modify(User $user, Enrollment $enrollment): bool
    {
        // Superadmin can modify any enrollment
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // User can modify their own enrollment
        if ((int) $enrollment->user_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can view the enrollment
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        // Superadmin can view any enrollment
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // User can view their own enrollment
        if ((int) $enrollment->user_id === (int) $user->id) {
            return true;
        }

        // Course managers can view enrollments for their courses
        if ($enrollment->course) {
            if ($user->hasAnyRole(['Admin', 'Instructor'])) {
                return $enrollment->course->hasInstructor($user) || $enrollment->course->hasAdmin($user);
            }
        }

        return false;
    }

    /**
     * Determine if the user can approve the enrollment
     */
    public function approve(User $user, Enrollment $enrollment): bool
    {
        // Superadmin can approve any enrollment
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // Course managers can approve enrollments
        if ($enrollment->course) {
            if ($user->hasAnyRole(['Admin', 'Instructor'])) {
                return $enrollment->course->hasInstructor($user) || $enrollment->course->hasAdmin($user);
            }
        }

        return false;
    }
}
