<?php

declare(strict_types=1);

namespace Modules\Schemes\Policies;

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Unit;

class UnitPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, Unit $unit): bool
    {
        $course = $unit->course;
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Student')) {
            return \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
        }

        return true;
    }

    public function create(User $user, \Modules\Schemes\Models\Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function update(User $user, Unit $unit): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $unit->course;
        if (!$course) {
            return false;
        }

        // Check if user is assigned admin for this course
        if ($user->hasRole('Admin') && $course->admins()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check if user is the instructor
        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function delete(User $user, Unit $unit): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $unit->course;
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin') && $course->admins()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function reorder(User $user, Unit $unit): bool
    {
        return $this->update($user, $unit);
    }
}
