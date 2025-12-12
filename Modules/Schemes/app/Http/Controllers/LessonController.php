<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Schemes\Http\Requests\LessonRequest;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\LessonService;
use Modules\Schemes\Services\ProgressionService;

/**
 * @tags Materi Pembelajaran
 */
class LessonController extends Controller
{
  use ApiResponse;

  public function __construct(
    private LessonService $service,
    private ProgressionService $progression,
  ) {}

  /**
   * Daftar Lesson
   *
   * Mengambil daftar lesson dalam sebuah unit kompetensi. Student harus enrolled di course untuk mengakses.
   *
   *
   * @summary Daftar Lesson
   * @allowedFilters status, content_type
   *
   * @queryParam filter[status] string Filter berdasarkan status (draft|published). Example: published
   * @queryParam filter[content_type] string Filter berdasarkan tipe konten (markdown|video|link). Example: video
   *
   * @allowedSorts order, title, created_at
   *
   * @queryParam sort string Field untuk sorting. Allowed: order, title, created_at. Prefix dengan '-' untuk descending. Example: -created_at
   *
   * @allowedIncludes blocks, unit
   *
   * @filterEnum status draft|published
   * @filterEnum content_type markdown|video|link
   *
   * @response 200 scenario="Success" {"success": true, "message": "Success", "data": [{"id": 1, "title": "Lesson 1: Pengenalan", "content_type": "markdown", "order": 1, "status": "published", "duration_minutes": 15}], "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 5}}
   * @response 403 scenario="Not Enrolled" {"success":false,"message":"Anda tidak memiliki akses untuk melihat lessons di course ini."}
   * @response 404 scenario="Unit Not Found" {"success":false,"message":"Unit tidak ditemukan di course ini."}
   *
   * @authenticated
   */
  public function index(Request $request, Course $course, Unit $unit)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $unitModel = $unit;
    if ((int) $unitModel->course_id !== (int) $course->id) {
      return $this->error(__("messages.units.not_in_course"), 404);
    }

    $courseModel = $course;

    $authorized = false;
    if ($user->hasRole("Superadmin")) {
      $authorized = true;
    } elseif ($user->hasRole("Admin")) {
      if ((int) $courseModel->instructor_id === (int) $user->id) {
        $authorized = true;
      } elseif (method_exists($courseModel, "hasAdmin") && $courseModel->hasAdmin($user)) {
        $authorized = true;
      }
    } elseif ($user->hasRole("Student")) {
      $enrollment = \Modules\Enrollments\Models\Enrollment::where("user_id", $user->id)
        ->where("course_id", $course)
        ->whereIn("status", ["active", "completed"])
        ->exists();
      if ($enrollment) {
        $authorized = true;
      }
    }

    if (!$authorized) {
      return $this->error(__("messages.lessons.no_view_list_access"), 403);
    }

    $params = $request->all();
    $paginator = $this->service->listByUnit($unit->id, $params);

    return $this->paginateResponse($paginator);
  }

  /**
   * Buat Lesson Baru
   *
   * Membuat lesson baru dalam sebuah unit. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Buat Lesson Baru
   * @response 201 scenario="Success" {"success": true, "message": "Lesson berhasil dibuat.", "data": {"lesson": {"id": 1, "title": "Lesson 1", "content_type": "markdown", "order": 1, "status": "draft"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda hanya dapat membuat lesson untuk course yang Anda buat atau course yang Anda kelola sebagai admin."}
   * @response 404 scenario="Unit Not Found" {"success":false,"message":"Unit tidak ditemukan di course ini."}
   *
   * @authenticated
   */
  public function store(LessonRequest $request, Course $course, Unit $unit)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $unitModel = $unit;
    if ((int) $unitModel->course_id !== (int) $course->id) {
      return $this->error(__("messages.units.not_in_course"), 404);
    }

    $courseModel = $course;

    $authorized = false;
    if ($user->hasRole("Superadmin")) {
      $authorized = true;
    } elseif ($user->hasRole("Admin")) {
      if ((int) $courseModel->instructor_id === (int) $user->id) {
        $authorized = true;
      } elseif (method_exists($courseModel, "hasAdmin") && $courseModel->hasAdmin($user)) {
        $authorized = true;
      }
    }

    if (!$authorized) {
      return $this->error(__("messages.lessons.no_create_access"), 403);
    }

    $data = $request->validated();
    $lesson = $this->service->create($unit->id, $data);

    return $this->created(["lesson" => $lesson], __("messages.lessons.created"));
  }

  /**
   * Detail Lesson
   *
   * Mengambil detail lesson termasuk content blocks. Student harus enrolled dan memenuhi prasyarat untuk mengakses.
   *
   *
   * @summary Detail Lesson
   * @response 200 scenario="Success" {"success": true, "data": {"lesson": {"id": 1, "title": "Lesson 1", "content_type": "markdown", "content": "# Pengenalan...", "duration_minutes": 15, "blocks": []}}}
   * @response 403 scenario="Locked" {"success":false,"message":"Lesson masih terkunci karena prasyarat belum selesai."}
   * @response 403 scenario="Not Enrolled" {"success":false,"message":"Anda belum terdaftar pada course ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Lesson tidak ditemukan."}
   *
   * @authenticated
   */
  public function show(Course $course, Unit $unit, Lesson $lesson)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $unitModel = $unit;
    if ((int) $unitModel->course_id !== (int) $course->id) {
      return $this->error(__("messages.units.not_in_course"), 404);
    }

    $found = $this->service->show($unit->id, $lesson->id);
    if (!$found) {
      return $this->error(__("messages.lessons.not_found"), 404);
    }

    $courseModel = $course;

    $authorized = false;
    if ($user->hasRole("Superadmin")) {
      $authorized = true;
    } elseif ($user->hasRole("Admin")) {
      if ((int) $courseModel->instructor_id === (int) $user->id) {
        $authorized = true;
      } elseif (method_exists($courseModel, "hasAdmin") && $courseModel->hasAdmin($user)) {
        $authorized = true;
      }
    } elseif ($user->hasRole("Student")) {
      $enrollment = \Modules\Enrollments\Models\Enrollment::where("user_id", $user->id)
        ->where("course_id", $course)
        ->whereIn("status", ["active", "completed"])
        ->exists();
      if ($enrollment) {
        $authorized = true;
      }
    }

    if (!$authorized) {
      return $this->error(__("messages.lessons.no_view_access"), 403);
    }

    if ($user->hasRole("Student")) {
      $enrollment = $this->progression->getEnrollmentForCourse($course->id, $user->id);
      if (!$enrollment) {
        return $this->error(__("messages.lessons.not_enrolled"), 403);
      }

      if (!$this->progression->canAccessLesson($lesson, $enrollment)) {
        return $this->error(__("messages.lessons.locked_prerequisite"), 403);
      }
    }

    return $this->success(["lesson" => $found]);
  }

  /**
   * Perbarui Lesson
   *
   * Memperbarui data lesson. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Perbarui Lesson
   * @response 200 scenario="Success" {"success": true, "message": "Lesson berhasil diperbarui.", "data": {"lesson": {"id": 1, "title": "Lesson 1 Updated"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mengubah lesson ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Lesson tidak ditemukan."}
   *
   * @authenticated
   */
  public function update(LessonRequest $request, Course $course, Unit $unit, Lesson $lesson)
  {
    $found = $this->service->show($unit->id, $lesson->id);
    if (!$found) {
      return $this->error(__("messages.lessons.not_found"), 404);
    }

    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!Gate::forUser($user)->allows("update", $found)) {
      return $this->error(__("messages.lessons.no_update_access"), 403);
    }

    $data = $request->validated();
    $updated = $this->service->update($unit->id, $lesson->id, $data);

    return $this->success(["lesson" => $updated], __("messages.lessons.updated"));
  }

  /**
   * Hapus Lesson
   *
   * Menghapus lesson beserta semua blocks di dalamnya. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Hapus Lesson
   * @response 200 scenario="Success" {"success":true,"message":"Lesson berhasil dihapus.","data":[]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menghapus lesson ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Lesson tidak ditemukan."}
   *
   * @authenticated
   */
  public function destroy(Course $course, Unit $unit, Lesson $lesson)
  {
    $found = $this->service->show($unit->id, $lesson->id);
    if (!$found) {
      return $this->error(__("messages.lessons.not_found"), 404);
    }

    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!Gate::forUser($user)->allows("delete", $found)) {
      return $this->error(__("messages.lessons.no_delete_access"), 403);
    }

    $ok = $this->service->delete($unit->id, $lesson->id);

    return $this->success([], __("messages.lessons.deleted"));
  }

  /**
   * Publish Lesson
   *
   * Mempublish lesson agar dapat diakses oleh student. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Publish Lesson
   * @response 200 scenario="Success" {"success": true, "message": "Lesson berhasil dipublish.", "data": {"lesson": {"id": 1, "status": "published"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mempublish lesson ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Lesson tidak ditemukan."}
   *
   * @authenticated
   */
  public function publish(Course $course, Unit $unit, Lesson $lesson)
  {
    $found = $this->service->show($unit->id, $lesson->id);
    if (!$found) {
      return $this->error(__("messages.lessons.not_found"), 404);
    }

    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!Gate::forUser($user)->allows("update", $found)) {
      return $this->error(__("messages.lessons.no_publish_access"), 403);
    }

    $updated = $this->service->publish($unit->id, $lesson->id);

    return $this->success(["lesson" => $updated], __("messages.lessons.published"));
  }

  /**
   * Unpublish Lesson
   *
   * Meng-unpublish lesson. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Unpublish Lesson
   * @response 200 scenario="Success" {"success": true, "message": "Lesson berhasil diunpublish.", "data": {"lesson": {"id": 1, "status": "draft"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk unpublish lesson ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Lesson tidak ditemukan."}
   *
   * @authenticated
   */
  public function unpublish(Course $course, Unit $unit, Lesson $lesson)
  {
    $found = $this->service->show($unit->id, $lesson->id);
    if (!$found) {
      return $this->error(__("messages.lessons.not_found"), 404);
    }

    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!Gate::forUser($user)->allows("update", $found)) {
      return $this->error(__("messages.lessons.no_unpublish_access"), 403);
    }

    $updated = $this->service->unpublish($unit->id, $lesson->id);

    return $this->success(["lesson" => $updated], __("messages.lessons.unpublished"));
  }
}
