<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Schemes\Http\Requests\ReorderUnitsRequest;
use Modules\Schemes\Http\Requests\UnitRequest;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\UnitService;

/**
 * @tags Unit Kompetensi
 */
class UnitController extends Controller
{
  use ApiResponse;

  public function __construct(private UnitService $service) {}

  /**
   * Daftar Unit Kompetensi
   *
   * Mengambil daftar unit kompetensi dalam sebuah kursus dengan pagination dan filter.
   *
   *
   * @summary Daftar Unit Kompetensi
   * @allowedFilters status
   *
   * @queryParam filter[status] string Filter berdasarkan status (draft|published). Example: published
   *
   * @allowedSorts order, title, created_at
   *
   * @queryParam sort string Field untuk sorting. Allowed: order, title, created_at. Prefix dengan '-' untuk descending. Example: -created_at
   *
   * @allowedIncludes lessons, course
   *
   * @filterEnum status draft|published
   *
   * @response 200 scenario="Success" {"success": true, "message": "Success", "data": [{"id": 1, "title": "Unit 1: Pengenalan", "code": "UK001", "order": 1, "status": "published", "lessons_count": 5}], "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 3}}
   * @response 404 scenario="Course Not Found" {"success":false,"message":"Course tidak ditemukan."}
   *
   * @authenticated
   */
  public function index(Request $request, Course $course)
  {
    $params = $request->all();
    $paginator = $this->service->listByCourse($course->id, $params);

    return $this->paginateResponse($paginator);
  }

  /**
   * Buat Unit Kompetensi Baru
   *
   * Membuat unit kompetensi baru dalam sebuah kursus. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Buat Unit Kompetensi Baru
   * @bodyParam title string required Judul unit kompetensi. Example: Unit 1: Pengenalan
   * @bodyParam code string required Kode unit. Example: UK001
   * @bodyParam description string optional Deskripsi unit. Example: Unit pengenalan dasar
   * @bodyParam order integer optional Urutan unit. Example: 1
   *
   * @response 201 scenario="Success" {"success": true, "message": "Unit berhasil dibuat.", "data": {"unit": {"id": 1, "title": "Unit 1: Pengenalan", "code": "UK001", "order": 1, "status": "draft"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda hanya dapat membuat unit untuk course yang Anda buat atau course yang Anda kelola sebagai admin."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"title": ["Judul wajib diisi."]}}
   *
   * @authenticated
   *
   * @role Admin|Superadmin
   */
  public function store(UnitRequest $request, Course $course)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

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
      return $this->error(__("messages.units.no_create_access"), 403);
    }

    $data = $request->validated();
    $unit = $this->service->create($course->id, $data);

    return $this->created(["unit" => $unit], __("messages.units.created"));
  }

  /**
   * Detail Unit Kompetensi
   *
   * Mengambil detail unit kompetensi termasuk lessons yang terkait.
   *
   *
   * @summary Detail Unit Kompetensi
   * @response 200 scenario="Success" {"success": true, "data": {"unit": {"id": 1, "title": "Unit 1: Pengenalan", "code": "UK001", "description": "Pengenalan dasar", "order": 1, "status": "published", "lessons": [{"id": 1, "title": "Lesson 1"}]}}}
   * @response 404 scenario="Not Found" {"success":false,"message":"Unit tidak ditemukan."}
   *
   * @authenticated
   */
  public function show(Course $course, Unit $unit)
  {
    $found = $this->service->show($course->id, $unit->id);
    if (!$found) {
      return $this->error(__("messages.units.not_found"), 404);
    }

    return $this->success(["unit" => $found]);
  }

  /**
   * Perbarui Unit Kompetensi
   *
   * Memperbarui data unit kompetensi. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Perbarui Unit Kompetensi
   * @response 200 scenario="Success" {"success": true, "message": "Unit berhasil diperbarui.", "data": {"unit": {"id": 1, "title": "Unit 1: Pengenalan Updated", "code": "UK001"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mengubah unit ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Unit tidak ditemukan."}
   *
   * @authenticated
   */
  public function update(UnitRequest $request, Course $course, Unit $unit)
  {
    $found = $this->service->show($course->id, $unit->id);
    if (!$found) {
      return $this->error(__("messages.units.not_found"), 404);
    }

    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!Gate::forUser($user)->allows("update", $found)) {
      return $this->error(__("messages.units.no_update_access"), 403);
    }

    $data = $request->validated();
    $updated = $this->service->update($course->id, $unit->id, $data);

    return $this->success(["unit" => $updated], __("messages.units.updated"));
  }

  /**
   * Hapus Unit Kompetensi
   *
   * Menghapus unit kompetensi beserta semua lessons di dalamnya. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Hapus Unit Kompetensi
   * @response 200 scenario="Success" {"success":true,"message":"Unit berhasil dihapus.","data":[]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menghapus unit ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Unit tidak ditemukan."}
   *
   * @authenticated
   */
  public function destroy(Course $course, Unit $unit)
  {
    $found = $this->service->show($course->id, $unit->id);
    if (!$found) {
      return $this->error(__("messages.units.not_found"), 404);
    }

    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!Gate::forUser($user)->allows("delete", $found)) {
      return $this->error(__("messages.units.no_delete_access"), 403);
    }

    $ok = $this->service->delete($course->id, $unit->id);

    return $this->success([], __("messages.units.deleted"));
  }

  /**
   * Ubah Urutan Unit
   *
   * Mengubah urutan unit kompetensi dalam sebuah kursus. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Ubah Urutan Unit
   * @response 200 scenario="Success" {"success":true,"message":"Urutan unit berhasil diperbarui.","data":[]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda hanya dapat mengatur urutan unit untuk course yang Anda buat atau course yang Anda kelola sebagai admin."}
   * @response 422 scenario="Invalid Units" {"success":false,"message":"Beberapa unit tidak ditemukan di course ini."}
   *
   * @authenticated
   */
  public function reorder(ReorderUnitsRequest $request, Course $course)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

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
      return $this->error(__("messages.units.no_reorder_access"), 403);
    }

    $data = $request->validated();

    $unitIds = $data["units"];
    $unitsInCourse = $this->service->getRepository()->getAllByCourse($course->id);
    $validUnitIds = $unitsInCourse->pluck("id")->toArray();
    $invalidIds = array_diff($unitIds, $validUnitIds);

    if (!empty($invalidIds)) {
      return $this->error(__("messages.units.some_not_found"), 422);
    }

    $unitOrders = [];
    foreach ($unitIds as $index => $unitId) {
      $unitOrders[$unitId] = $index + 1;
    }
    $this->service->reorder($course->id, $unitOrders);

    return $this->success([], __("messages.units.order_updated"));
  }

  /**
   * Publish Unit
   *
   * Mempublish unit kompetensi agar dapat diakses oleh student. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Publish Unit
   * @response 200 scenario="Success" {"success": true, "message": "Unit berhasil dipublish.", "data": {"unit": {"id": 1, "status": "published"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mempublish unit ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Unit tidak ditemukan."}
   *
   * @authenticated
   */
  public function publish(Course $course, Unit $unit)
  {
    $found = $this->service->show($course->id, $unit->id);
    if (!$found) {
      return $this->error(__("messages.units.not_found"), 404);
    }

    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!Gate::forUser($user)->allows("update", $found)) {
      return $this->error(__("messages.units.no_publish_access"), 403);
    }

    $updated = $this->service->publish($course->id, $unit->id);

    return $this->success(["unit" => $updated], __("messages.units.published"));
  }

  /**
   * Unpublish Unit
   *
   * Meng-unpublish unit kompetensi. **Memerlukan role: Admin atau Superadmin (owner course)**
   *
   *
   * @summary Unpublish Unit
   * @response 200 scenario="Success" {"success": true, "message": "Unit berhasil diunpublish.", "data": {"unit": {"id": 1, "status": "draft"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk unpublish unit ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Unit tidak ditemukan."}
   *
   * @authenticated
   */
  public function unpublish(Course $course, Unit $unit)
  {
    $found = $this->service->show($course->id, $unit->id);
    if (!$found) {
      return $this->error(__("messages.units.not_found"), 404);
    }

    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!Gate::forUser($user)->allows("update", $found)) {
      return $this->error(__("messages.units.no_unpublish_access"), 403);
    }

    $updated = $this->service->unpublish($course->id, $unit->id);

    return $this->success(["unit" => $updated], __("messages.units.unpublished"));
  }
}
