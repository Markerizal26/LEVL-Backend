<?php

declare(strict_types=1);

namespace Modules\Schemes\Policies;

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

class CoursePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Course $course): bool
    {
        
        if ($course->status === \Modules\Schemes\Enums\CourseStatus::Published) {
            return true;
        }

        
        if (! $user) {
            return false;
        }

        
        if ($user->hasRole('Student')) {
            return false;
        }

        
        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        
        if ($user->hasRole('Instructor')) {
            return $course->instructors()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin') || $user->hasRole('Admin');
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public function publish(User $user, Course $course): bool
    {
        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public function viewAssignments(User $user, Course $course): bool
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

        
        if ($user->hasRole('Student')) {
            return \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
        }

        return false;
    }

    public function manageContent(User $user, Course $course): bool
    {
        if ($this->update($user, $course)) {
            return true;
        }

        if ($user->hasRole('Instructor')) {
            return $course->instructors()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function viewUnits(?User $user, Course $course): bool
    {
        return $this->view($user, $course);
    }
}
