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
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        $courseId = $assignment->getCourseId();
        if (!$courseId) {
            return false;
        }

        if ($user->hasRole('Instructor')) {
            $assignment->loadMissing('lesson.unit.course');
            return $assignment->lesson?->unit?->course?->instructor_id === $user->id;
        }

        if ($user->hasRole('Student')) {
            return \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $courseId)
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
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $courseId = $assignment->getCourseId();
        
        if (!$courseId) {
            return false;
        }

        $course = \Modules\Schemes\Models\Course::find($courseId);
        
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $courseId = $assignment->getCourseId();
        
        if (!$courseId) {
            return false;
        }

        $course = \Modules\Schemes\Models\Course::find($courseId);
        
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function publish(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function grantOverride(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $courseId = $assignment->getCourseId();
        
        if (!$courseId) {
            return false;
        }

        $course = \Modules\Schemes\Models\Course::find($courseId);
        
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function viewOverrides(User $user, Assignment $assignment): bool
    {
        return $this->grantOverride($user, $assignment);
    }

    public function duplicate(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $courseId = $assignment->getCourseId();
        
        if (!$courseId) {
            return false;
        }

        $course = \Modules\Schemes\Models\Course::find($courseId);
        
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }
}
