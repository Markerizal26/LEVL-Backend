<?php

declare(strict_types=1);

namespace Modules\Learning\Policies;

use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        if ($submission->user_id === $user->id) {
            return true;
        }

        $submission->loadMissing('assignment.lesson.unit.course');
        $course = $submission->assignment?->lesson?->unit?->course;
        
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Instructor')) {
            return $course->instructor_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Student') || $user->hasRole('Superadmin');
    }

    public function createForAssignment(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if (!$user->hasRole('Student')) {
            return false;
        }

        $courseId = $assignment->getCourseId();
        if (!$courseId) {
            return false;
        }

        return \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }

    public function update(User $user, Submission $submission): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $submission->user_id === $user->id && $submission->status === 'draft';
    }

    public function delete(User $user, Submission $submission): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $submission->user_id === $user->id && $submission->status === 'draft';
    }

    public function grade(User $user, Submission $submission): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $submission->loadMissing('assignment.lesson.unit.course');
        $course = $submission->assignment?->lesson?->unit?->course;
        
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $course->admins()->where('user_id', $user->id)->exists();
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }
}
