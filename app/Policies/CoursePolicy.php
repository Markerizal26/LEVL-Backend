<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

class CoursePolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return Response::allow();
        }
        return $this->deny('Hanya admin atau superadmin yang dapat membuat course.');
    }

    public function update(User $user, Course $course)
    {
        if ($user->hasRole('superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('admin')) {
            return $this->deny('Hanya admin atau superadmin yang dapat mengubah course.');
        }
        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }
        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny('Anda hanya dapat mengubah course yang Anda buat atau ketika Anda terdaftar sebagai admin course.');
    }

    public function delete(User $user, Course $course)
    {
        if ($user->hasRole('superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('admin')) {
            return $this->deny('Hanya admin atau superadmin yang dapat menghapus course.');
        }
        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }
        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny('Anda hanya dapat menghapus course yang Anda buat atau ketika Anda terdaftar sebagai admin course.');
    }
}
