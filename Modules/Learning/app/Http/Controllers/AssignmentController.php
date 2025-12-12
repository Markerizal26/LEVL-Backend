<?php

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\SubmissionType;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Services\AssignmentService;

/**
 * @tags Tugas & Pengumpulan
 */
class AssignmentController extends Controller
{
  use ApiResponse;

  public function __construct(private AssignmentService $service) {}

  /**
   * Daftar Tugas
   *
   * Mengambil daftar tugas dalam sebuah lesson.
   *
   *
   * @summary Daftar Tugas
   *
   * @response 200 scenario="Success" {"success": true, "data": {"assignments": [{"id": 1, "title": "Tugas 1", "submission_type": "file", "status": "published", "deadline_at": "2024-01-20T23:59:59Z"}]}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function index(
    Request $request,
    \Modules\Schemes\Models\Course $course,
    \Modules\Schemes\Models\Unit $unit,
    \Modules\Schemes\Models\Lesson $lesson,
  ) {
    $assignments = $this->service->listByLesson($lesson, $request->all());

    return $this->success(["assignments" => $assignments]);
  }

  /**
   * Buat Tugas Baru
   *
   * Membuat tugas baru dalam sebuah lesson. **Memerlukan role: Admin atau Instructor (owner course)**
   *
   *
   * @summary Buat Tugas Baru
   *
   * @response 201 scenario="Success" {"success": true, "message": "Assignment berhasil dibuat.", "data": {"assignment": {"id": 1, "title": "Tugas Baru", "submission_type": "file", "status": "draft"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk membuat assignment di course ini."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"title": ["Judul wajib diisi."]}}
   *
   * @authenticated
   */
  public function store(
    Request $request,
    \Modules\Schemes\Models\Course $course,
    \Modules\Schemes\Models\Unit $unit,
    \Modules\Schemes\Models\Lesson $lesson,
  ) {
    $this->authorize("create", Assignment::class);

    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $validated = $request->validate([
      "title" => ["required", "string", "max:255"],
      "description" => ["nullable", "string"],
      "submission_type" => ["required", Rule::enum(SubmissionType::class)],
      "max_score" => ["nullable", "integer", "min:1", "max:1000"],
      "available_from" => ["nullable", "date"],
      "deadline_at" => ["nullable", "date", "after_or_equal:available_from"],
      "status" => ["nullable", Rule::enum(AssignmentStatus::class)],
      "allow_resubmit" => ["nullable", "boolean"],
      "late_penalty_percent" => ["nullable", "integer", "min:0", "max:100"],
    ]);

    $validated["lesson_id"] = $lesson->id;

    $assignment = $this->service->create($validated, $user->id);

    return $this->created(["assignment" => $assignment], __("messages.assignments.created"));
  }

  /**
   * Detail Tugas
   *
   * Mengambil detail tugas termasuk informasi creator dan lesson.
   *
   *
   * @summary Detail Tugas
   *
   * @response 200 scenario="Success" {"success": true, "data": {"assignment": {"id": 1, "title": "Tugas 1", "description": "Deskripsi tugas...", "submission_type": "file", "max_score": 100, "deadline_at": "2024-01-20T23:59:59Z", "creator": {"id": 1, "name": "Instructor"}, "lesson": {"id": 1, "title": "Lesson 1"}}}}
   * @response 404 scenario="Not Found" {"success":false,"message":"Assignment tidak ditemukan."}
   *
   * @authenticated
   */
  public function show(Assignment $assignment)
  {
    return $this->success(["assignment" => AssignmentResource::make($assignment)]);
  }

  /**
   * Perbarui Tugas
   *
   * Memperbarui data tugas. **Memerlukan role: Admin atau Instructor (owner course)**
   *
   *
   * @summary Perbarui Tugas
   *
   * @response 200 scenario="Success" {"success": true, "message": "Assignment berhasil diperbarui.", "data": {"assignment": {"id": 1, "title": "Tugas Updated"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mengubah assignment ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Assignment tidak ditemukan."}
   *
   * @authenticated
   */
  public function update(Request $request, Assignment $assignment)
  {
    $this->authorize("update", $assignment);

    $validated = $request->validate([
      "title" => ["sometimes", "string", "max:255"],
      "description" => ["nullable", "string"],
      "submission_type" => ["sometimes", Rule::enum(SubmissionType::class)],
      "max_score" => ["nullable", "integer", "min:1", "max:1000"],
      "available_from" => ["nullable", "date"],
      "deadline_at" => ["nullable", "date", "after_or_equal:available_from"],
      "status" => ["sometimes", Rule::enum(AssignmentStatus::class)],
      "allow_resubmit" => ["nullable", "boolean"],
      "late_penalty_percent" => ["nullable", "integer", "min:0", "max:100"],
    ]);

    $updated = $this->service->update($assignment, $validated);

    return $this->success(["assignment" => $updated], __("messages.assignments.updated"));
  }

  /**
   * Hapus Tugas
   *
   * Menghapus tugas. **Memerlukan role: Admin atau Instructor (owner course)**
   *
   *
   * @summary Hapus Tugas
   *
   * @response 200 scenario="Success" {"success":true,"message":"Assignment berhasil dihapus.","data":[]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menghapus assignment ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Assignment tidak ditemukan."}
   *
   * @authenticated
   */
  public function destroy(Assignment $assignment)
  {
    $this->authorize("delete", $assignment);

    $this->service->delete($assignment);

    return $this->success([], __("messages.assignments.deleted"));
  }

  /**
   * Publish Tugas
   *
   * Mempublish tugas agar dapat diakses oleh student. **Memerlukan role: Admin atau Instructor (owner course)**
   *
   *
   * @summary Publish Tugas
   *
   * @response 200 scenario="Success" {"success": true, "message": "Assignment berhasil dipublish.", "data": {"assignment": {"id": 1, "status": "published"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mempublish assignment ini."}
   *
   * @authenticated
   */
  public function publish(Assignment $assignment)
  {
    $this->authorize("publish", $assignment);

    $updated = $this->service->publish($assignment);

    return $this->success(["assignment" => $updated], __("messages.assignments.published"));
  }

  /**
   * Unpublish Tugas
   *
   * Meng-unpublish tugas. **Memerlukan role: Admin atau Instructor (owner course)**
   *
   *
   * @summary Unpublish Tugas
   *
   * @response 200 scenario="Success" {"success": true, "message": "Assignment berhasil diunpublish.", "data": {"assignment": {"id": 1, "status": "draft"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk unpublish assignment ini."}
   *
   * @authenticated
   */
  public function unpublish(Assignment $assignment)
  {
    $this->authorize("publish", $assignment);

    $updated = $this->service->unpublish($assignment);

    return $this->success(["assignment" => $updated], __("messages.assignments.unpublished"));
  }
}
