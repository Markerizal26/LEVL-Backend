<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Schemes\Http\Requests\LessonBlockRequest;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\LessonBlock;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\LessonBlockService;

/**
 * @tags Materi Pembelajaran
 */
class LessonBlockController extends Controller
{
    use ApiResponse;

    public function __construct(private LessonBlockService $service) {}

    /**
     * Daftar Blok Lesson
     *
     *
     * @summary Daftar Blok Lesson
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example LessonBlock"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function index(Course $course, Unit $unit, Lesson $lesson)
    {
        $lessonModel = $lesson->load(['unit.course']);
        if ((int) ($lessonModel->unit?->course_id) !== (int) $course->id || (int) ($lessonModel->unit_id) !== (int) $unit->id) {
            return $this->error('Lesson tidak ditemukan di course ini.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $course = $lessonModel->unit?->course;
        if (! $course) {
            return $this->error('Course tidak ditemukan.', 404);
        }

        $authorized = false;
        if ($user->hasRole('Superadmin')) {
            $authorized = true;
        } elseif ($user->hasRole('Admin')) {
            if ((int) $course->instructor_id === (int) $user->id) {
                $authorized = true;
            } elseif (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
                $authorized = true;
            }
        } elseif ($user->hasRole('Student')) {
            $enrolled = \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
            if ($enrolled) {
                $authorized = true;
            }
        }

        if (! $authorized) {
            return $this->error('Anda tidak memiliki akses untuk melihat blok lesson ini.', 403);
        }

        $blocks = $this->service->list($lesson->id);

        return $this->success(['blocks' => $blocks]);
    }

    /**
     * Buat Blok Lesson Baru
     *
     *
     * @summary Buat Blok Lesson Baru
     *
     * @response 201 scenario="Success" {"success":true,"message":"LessonBlock berhasil dibuat.","data":{"id":1,"name":"New LessonBlock"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function store(LessonBlockRequest $request, Course $course, Unit $unit, Lesson $lesson)
    {
        $lessonModel = $lesson->load(['unit.course']);
        if ((int) ($lessonModel->unit?->course_id) !== (int) $course->id || (int) ($lessonModel->unit_id) !== (int) $unit->id) {
            return $this->error('Lesson tidak ditemukan di course ini.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        $course = $lessonModel->unit?->course;
        if (! $course) {
            return $this->error('Course tidak ditemukan.', 404);
        }

        $authorized = false;
        if ($user->hasRole('Superadmin')) {
            $authorized = true;
        } elseif ($user->hasRole('Admin')) {
            if ((int) $course->instructor_id === (int) $user->id) {
                $authorized = true;
            } elseif (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
                $authorized = true;
            }
        }

        if (! $authorized) {
            return $this->error('Anda hanya dapat mengelola blok untuk course yang Anda kelola.', 403);
        }

        $data = $request->validated();
        $mediaFile = $request->file('media');
        $block = $this->service->create($lesson->id, $data, $mediaFile);

        return $this->created(['block' => $block], 'Blok lesson berhasil dibuat.');
    }

    /**
     * Detail Blok Lesson
     *
     *
     * @summary Detail Blok Lesson
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example LessonBlock"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"LessonBlock tidak ditemukan."}
     * @authenticated
     */
    public function show(Course $course, Unit $unit, Lesson $lesson, LessonBlock $block)
    {
        $lessonModel = $lesson->load(['unit.course']);
        if ((int) ($lessonModel->unit?->course_id) !== (int) $course->id || (int) ($lessonModel->unit_id) !== (int) $unit->id) {
            return $this->error('Lesson tidak ditemukan di course ini.', 404);
        }

        $found = $block;
        if ((int) $found->lesson_id !== (int) $lesson->id) {
            return $this->error('Blok lesson tidak ditemukan.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        $course = $lessonModel->unit?->course;

        $authorized = false;
        if ($user->hasRole('Superadmin')) {
            $authorized = true;
        } elseif ($user->hasRole('Admin')) {
            if ((int) $course->instructor_id === (int) $user->id) {
                $authorized = true;
            } elseif (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
                $authorized = true;
            }
        } elseif ($user->hasRole('Student')) {
            $enrolled = \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
            if ($enrolled) {
                $authorized = true;
            }
        }

        if (! $authorized) {
            return $this->error('Anda tidak memiliki akses untuk melihat blok lesson ini.', 403);
        }

        return $this->success(['block' => $found]);
    }

    /**
     * Perbarui Blok Lesson
     *
     *
     * @summary Perbarui Blok Lesson
     *
     * @response 200 scenario="Success" {"success":true,"message":"LessonBlock berhasil diperbarui.","data":{"id":1,"name":"Updated LessonBlock"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"LessonBlock tidak ditemukan."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function update(LessonBlockRequest $request, Course $course, Unit $unit, Lesson $lesson, LessonBlock $block)
    {
        $lessonModel = $lesson->load(['unit.course']);
        if ((int) ($lessonModel->unit?->course_id) !== (int) $course->id || (int) ($lessonModel->unit_id) !== (int) $unit->id) {
            return $this->error('Lesson tidak ditemukan di course ini.', 404);
        }

        $found = $block;
        if ((int) $found->lesson_id !== (int) $lesson->id) {
            return $this->error('Blok lesson tidak ditemukan.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! Gate::forUser($user)->allows('update', $lessonModel)) {
            return $this->error('Anda tidak memiliki akses untuk mengubah blok ini.', 403);
        }

        $data = $request->validated();
        $mediaFile = $request->file('media');
        $updated = $this->service->update($lesson->id, $found->id, $data, $mediaFile);

        return $this->success(['block' => $updated], 'Blok lesson berhasil diperbarui.');
    }

    /**
     * Hapus Blok Lesson
     *
     *
     * @summary Hapus Blok Lesson
     *
     * @response 200 scenario="Success" {"success":true,"message":"LessonBlock berhasil dihapus.","data":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"LessonBlock tidak ditemukan."}
     * @authenticated
     */
    public function destroy(Course $course, Unit $unit, Lesson $lesson, LessonBlock $block)
    {
        $lessonModel = $lesson->load(['unit.course']);
        if ((int) ($lessonModel->unit?->course_id) !== (int) $course->id || (int) ($lessonModel->unit_id) !== (int) $unit->id) {
            return $this->error('Lesson tidak ditemukan di course ini.', 404);
        }

        $found = $block;
        if ((int) $found->lesson_id !== (int) $lesson->id) {
            return $this->error('Blok lesson tidak ditemukan.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! Gate::forUser($user)->allows('delete', $lessonModel)) {
            return $this->error('Anda tidak memiliki akses untuk menghapus blok ini.', 403);
        }

        $this->service->delete($lesson->id, $found->id);

        return $this->success([], 'Blok lesson berhasil dihapus.');
    }
}
