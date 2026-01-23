<?php declare(strict_types=1);

namespace Modules\Schemes\Policies;

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Lesson;

class LessonPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, Lesson $lesson): bool
    {
        $course = $lesson->unit?->course;
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

    public function create(User $user, \Modules\Schemes\Models\Unit $unit): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $unit->course;
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function update(User $user, Lesson $lesson): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $lesson->unit?->course;
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin') && $course->admins()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $lesson->unit?->course;
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin') && $course->admins()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function reorder(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }

    public function manageContent(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }
}
