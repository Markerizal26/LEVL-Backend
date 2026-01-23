<?php

declare(strict_types=1);

namespace Modules\Enrollments\Policies;

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Superadmin');
    }

    public function viewByCourse(User $user, Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $course->hasAdmin($user);
        }

        if ($user->hasRole('Instructor')) {
            return $course->hasInstructor($user);
        }

        return false;
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ((int) $enrollment->user_id === (int) $user->id) {
            return true;
        }

        if ($enrollment->course) {
            if ($user->hasAnyRole(['Admin', 'Instructor'])) {
                return $enrollment->course->hasInstructor($user) || $enrollment->course->hasAdmin($user);
            }
        }

        return false;
    }

    public function modify(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ((int) $enrollment->user_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    public function cancel(User $user, Enrollment $enrollment): bool
    {
        return $this->modify($user, $enrollment);
    }

    public function withdraw(User $user, Enrollment $enrollment): bool
    {
        return $this->modify($user, $enrollment);
    }

    public function approve(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $this->isCourseManager($user, $enrollment);
    }

    public function decline(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $this->isCourseManager($user, $enrollment);
    }

    public function remove(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $this->isCourseManager($user, $enrollment);
    }

    private function isCourseManager(User $user, Enrollment $enrollment): bool
    {
        if (! $enrollment->course) {
            return false;
        }

        if ($user->hasAnyRole(['Admin', 'Instructor'])) {
            return $enrollment->course->hasInstructor($user) || $enrollment->course->hasAdmin($user);
        }

        return false;
    }
}
