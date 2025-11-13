<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Unit;

class UnitPolicy
{
    use HandlesAuthorization;

    public function create(User $user, int $courseId)
    {
        if ($user->hasRole('superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('admin')) {
            return $this->deny('Hanya admin atau superadmin yang dapat membuat unit.');
        }

        $course = \Modules\Schemes\Models\Course::find($courseId);
        if (! $course) {
            return $this->deny('Course tidak ditemukan.');
        }

        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }

        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny('Anda hanya dapat membuat unit untuk course yang Anda buat atau course yang Anda kelola sebagai admin.');
    }

    public function update(User $user, Unit $unit)
    {
        if ($user->hasRole('superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('admin')) {
            return $this->deny('Hanya admin atau superadmin yang dapat mengubah unit.');
        }

        $course = $unit->course;
        if (! $course) {
            return $this->deny('Course tidak ditemukan.');
        }

        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }

        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny('Anda hanya dapat mengubah unit dari course yang Anda buat atau course yang Anda kelola sebagai admin.');
    }

    public function delete(User $user, Unit $unit)
    {
        if ($user->hasRole('superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('admin')) {
            return $this->deny('Hanya admin atau superadmin yang dapat menghapus unit.');
        }

        $course = $unit->course;
        if (! $course) {
            return $this->deny('Course tidak ditemukan.');
        }

        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }

        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny('Anda hanya dapat menghapus unit dari course yang Anda buat atau course yang Anda kelola sebagai admin.');
    }
}
