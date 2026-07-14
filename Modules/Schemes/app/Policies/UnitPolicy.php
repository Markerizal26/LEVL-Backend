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

    public function view(?User $user, Unit $unit): bool
    {
        $course = $unit->course;
        if (! $course) {
            return false;
        }

        if ($unit->status === 'published' && $course->status === 'published') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        
        if ($user->hasRole('Admin')) {
            return true;
        }

        
        if ($user->hasRole('Instructor')) {
            return $course->instructors()->where('user_id', $user->id)->exists();
        }

        
        if ($user->hasRole('Student')) {
            return \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
        }

        return false;
    }

    public function create(User $user, \Modules\Schemes\Models\Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Instructor')) {
            return $course->instructors()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function update(User $user, Unit $unit): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $unit->course;
        if (! $course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Instructor')) {
            return $course->instructors()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function delete(User $user, Unit $unit): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $unit->course;
        if (! $course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Instructor')) {
            return $course->instructors()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function reorder(User $user, Unit $unit): bool
    {
        return $this->update($user, $unit);
    }
}
