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
        if ($course->status === 'published') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        return $course->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin') || $user->hasRole('Admin') || $user->hasRole('Instructor');
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // Check if user is assigned admin for this course
        if ($user->hasRole('Admin')) {
            if ($course->relationLoaded('admins')) {
                return $course->admins->contains('id', $user->id);
            }
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        // Check if user is the instructor
        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function delete(User $user, Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // Check if user is assigned admin for this course
        if ($user->hasRole('Admin')) {
            if ($course->relationLoaded('admins')) {
                return $course->admins->contains('id', $user->id);
            }
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        // Check if user is the instructor
        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function publish(User $user, Course $course): bool
    {
        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        // Allow instructor to publish their own course
        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function viewAssignments(User $user, Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            if ($course->relationLoaded('admins')) {
                return $course->admins->contains('id', $user->id);
            }
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        if ($user->hasRole('Instructor')) {
            return $course->instructor_id === $user->id;
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
        return $this->update($user, $course);
    }
}
