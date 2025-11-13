<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\ProgressionService;

class ProgressController extends Controller
{
    use ApiResponse;

    public function __construct(private ProgressionService $progression) {}

    public function show(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return $this->error('Anda belum login.', 401);
        }

        $targetUserId = (int) ($request->query('user_id') ?? $user->id);

        $isStudent = $user->hasRole('student');
        $isAdmin = $user->hasRole('admin') || $user->hasRole('superadmin');
        $isInstructor = $user->hasRole('instructor');

        $authorized = false;

        if ($isStudent) {
            if ($targetUserId !== (int) $user->id) {
                return $this->error('Anda tidak diperbolehkan melihat progress peserta lain.', 403);
            }
            $authorized = true;
        } elseif ($isAdmin) {
            $authorized = true;
            if (! $request->has('user_id')) {
                return $this->validationError(['user_id' => ['Parameter user_id wajib diisi untuk melihat progress peserta lain.']]);
            }
        } elseif ($isInstructor) {
            $managesCourse = $course->hasInstructor($user) || $course->hasAdmin($user);
            if (! $managesCourse) {
                return $this->error('Anda tidak memiliki akses ke course ini.', 403);
            }

            if (! $request->has('user_id')) {
                return $this->validationError(['user_id' => ['Parameter user_id wajib diisi untuk melihat progress peserta lain.']]);
            }

            $authorized = true;
        }

        if (! $authorized) {
            return $this->error('Peran Anda tidak diperbolehkan mengakses progress course.', 403);
        }

        $enrollment = $this->progression->getEnrollmentForCourse($course->id, $targetUserId);
        if (! $enrollment) {
            return $this->error('Progress peserta tidak ditemukan atau peserta belum terdaftar.', 404);
        }

        $data = $this->progression->getCourseProgressData($course, $enrollment);

        return $this->success($data);
    }

    public function completeLesson(Request $request, Course $course, Unit $unit, Lesson $lesson)
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return $this->error('Anda belum login.', 401);
        }

        if (! $user->hasRole('student')) {
            return $this->error('Hanya peserta yang dapat menandai lesson sebagai selesai.', 403);
        }

        if ((int) $unit->course_id !== (int) $course->id) {
            return $this->error('Unit tidak ditemukan di course ini.', 404);
        }

        if ((int) $lesson->unit_id !== (int) $unit->id) {
            return $this->error('Lesson tidak ditemukan di unit ini.', 404);
        }

        if ($lesson->status !== 'published') {
            return $this->error('Lesson belum tersedia.', 403);
        }

        $enrollment = $this->progression->getEnrollmentForCourse($course->id, $user->id);
        if (! $enrollment) {
            return $this->error('Anda belum terdaftar pada course ini.', 403);
        }

        if (! $this->progression->canAccessLesson($lesson, $enrollment)) {
            return $this->error('Lesson masih terkunci karena prasyarat belum selesai.', 403);
        }

        $this->progression->markLessonCompleted($lesson, $enrollment);

        $data = $this->progression->getCourseProgressData($course, $enrollment);

        return $this->success($data, 'Progress berhasil diperbarui.');
    }
}


