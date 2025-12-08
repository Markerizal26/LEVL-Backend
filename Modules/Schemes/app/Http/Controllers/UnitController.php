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
     * @summary Daftar Unit Kompetensi
     *
     * @description Mengambil daftar unit kompetensi dalam sebuah kursus dengan pagination dan filter.
     *
     * @allowedFilters status
     *
     * @allowedSorts order, title, created_at
     *
     * @allowedIncludes lessons, course
     *
     * @filterEnum status draft|published
     *
     * @response 200 scenario="Success" {"success": true, "message": "Success", "data": [{"id": 1, "title": "Unit 1: Pengenalan", "code": "UK001", "order": 1, "status": "published", "lessons_count": 5}], "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 3}}
     * @response 404 scenario="Course Not Found" {"success": false, "message": "Course tidak ditemukan."}
     */
    public function index(Request $request, Course $course)
    {
        $params = $request->all();
        $paginator = $this->service->listByCourse($course->id, $params);

        return $this->paginateResponse($paginator);
    }

    /**
     * @summary Buat Unit Kompetensi Baru
     *
     * @description Membuat unit kompetensi baru dalam sebuah kursus. **Memerlukan role: Admin atau Superadmin (owner course)**
     *
     * @response 201 scenario="Success" {"success": true, "message": "Unit berhasil dibuat.", "data": {"unit": {"id": 1, "title": "Unit 1: Pengenalan", "code": "UK001", "order": 1, "status": "draft"}}}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda hanya dapat membuat unit untuk course yang Anda buat atau course yang Anda kelola sebagai admin."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"title": ["Judul wajib diisi."]}}
     */
    public function store(UnitRequest $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $courseModel = $course;

        $authorized = false;
        if ($user->hasRole('Superadmin')) {
            $authorized = true;
        } elseif ($user->hasRole('Admin')) {
            if ((int) $courseModel->instructor_id === (int) $user->id) {
                $authorized = true;
            } elseif (method_exists($courseModel, 'hasAdmin') && $courseModel->hasAdmin($user)) {
                $authorized = true;
            }
        }

        if (! $authorized) {
            return $this->error('Anda hanya dapat membuat unit untuk course yang Anda buat atau course yang Anda kelola sebagai admin.', 403);
        }

        $data = $request->validated();
        $unit = $this->service->create($course->id, $data);

        return $this->created(['unit' => $unit], 'Unit berhasil dibuat.');
    }

    /**
     * @summary Detail Unit Kompetensi
     *
     * @description Mengambil detail unit kompetensi termasuk lessons yang terkait.
     *
     * @response 200 scenario="Success" {"success": true, "data": {"unit": {"id": 1, "title": "Unit 1: Pengenalan", "code": "UK001", "description": "Pengenalan dasar", "order": 1, "status": "published", "lessons": [{"id": 1, "title": "Lesson 1"}]}}}
     * @response 404 scenario="Not Found" {"success": false, "message": "Unit tidak ditemukan."}
     */
    public function show(Course $course, Unit $unit)
    {
        $found = $this->service->show($course->id, $unit->id);
        if (! $found) {
            return $this->error('Unit tidak ditemukan.', 404);
        }

        return $this->success(['unit' => $found]);
    }

    /**
     * @summary Perbarui Unit Kompetensi
     *
     * @description Memperbarui data unit kompetensi. **Memerlukan role: Admin atau Superadmin (owner course)**
     *
     * @response 200 scenario="Success" {"success": true, "message": "Unit berhasil diperbarui.", "data": {"unit": {"id": 1, "title": "Unit 1: Pengenalan Updated", "code": "UK001"}}}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk mengubah unit ini."}
     * @response 404 scenario="Not Found" {"success": false, "message": "Unit tidak ditemukan."}
     */
    public function update(UnitRequest $request, Course $course, Unit $unit)
    {
        $found = $this->service->show($course->id, $unit->id);
        if (! $found) {
            return $this->error('Unit tidak ditemukan.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! Gate::forUser($user)->allows('update', $found)) {
            return $this->error('Anda tidak memiliki akses untuk mengubah unit ini.', 403);
        }

        $data = $request->validated();
        $updated = $this->service->update($course->id, $unit->id, $data);

        return $this->success(['unit' => $updated], 'Unit berhasil diperbarui.');
    }

    /**
     * @summary Hapus Unit Kompetensi
     *
     * @description Menghapus unit kompetensi beserta semua lessons di dalamnya. **Memerlukan role: Admin atau Superadmin (owner course)**
     *
     * @response 200 scenario="Success" {"success": true, "message": "Unit berhasil dihapus.", "data": []}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk menghapus unit ini."}
     * @response 404 scenario="Not Found" {"success": false, "message": "Unit tidak ditemukan."}
     */
    public function destroy(Course $course, Unit $unit)
    {
        $found = $this->service->show($course->id, $unit->id);
        if (! $found) {
            return $this->error('Unit tidak ditemukan.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! Gate::forUser($user)->allows('delete', $found)) {
            return $this->error('Anda tidak memiliki akses untuk menghapus unit ini.', 403);
        }

        $ok = $this->service->delete($course->id, $unit->id);

        return $this->success([], 'Unit berhasil dihapus.');
    }

    /**
     * @summary Ubah Urutan Unit
     *
     * @description Mengubah urutan unit kompetensi dalam sebuah kursus. **Memerlukan role: Admin atau Superadmin (owner course)**
     *
     * @response 200 scenario="Success" {"success": true, "message": "Urutan unit berhasil diperbarui.", "data": []}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda hanya dapat mengatur urutan unit untuk course yang Anda buat atau course yang Anda kelola sebagai admin."}
     * @response 422 scenario="Invalid Units" {"success": false, "message": "Beberapa unit tidak ditemukan di course ini."}
     */
    public function reorder(ReorderUnitsRequest $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $courseModel = $course;

        $authorized = false;
        if ($user->hasRole('Superadmin')) {
            $authorized = true;
        } elseif ($user->hasRole('Admin')) {
            if ((int) $courseModel->instructor_id === (int) $user->id) {
                $authorized = true;
            } elseif (method_exists($courseModel, 'hasAdmin') && $courseModel->hasAdmin($user)) {
                $authorized = true;
            }
        }

        if (! $authorized) {
            return $this->error('Anda hanya dapat mengatur urutan unit untuk course yang Anda buat atau course yang Anda kelola sebagai admin.', 403);
        }

        $data = $request->validated();

        $unitIds = $data['units'];
        $unitsInCourse = $this->service->getRepository()->getAllByCourse($course->id);
        $validUnitIds = $unitsInCourse->pluck('id')->toArray();
        $invalidIds = array_diff($unitIds, $validUnitIds);

        if (! empty($invalidIds)) {
            return $this->error('Beberapa unit tidak ditemukan di course ini.', 422);
        }

        $unitOrders = [];
        foreach ($unitIds as $index => $unitId) {
            $unitOrders[$unitId] = $index + 1;
        }
        $this->service->reorder($course->id, $unitOrders);

        return $this->success([], 'Urutan unit berhasil diperbarui.');
    }

    /**
     * @summary Publish Unit
     *
     * @description Mempublish unit kompetensi agar dapat diakses oleh student. **Memerlukan role: Admin atau Superadmin (owner course)**
     *
     * @response 200 scenario="Success" {"success": true, "message": "Unit berhasil dipublish.", "data": {"unit": {"id": 1, "status": "published"}}}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk mempublish unit ini."}
     * @response 404 scenario="Not Found" {"success": false, "message": "Unit tidak ditemukan."}
     */
    public function publish(Course $course, Unit $unit)
    {
        $found = $this->service->show($course->id, $unit->id);
        if (! $found) {
            return $this->error('Unit tidak ditemukan.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! Gate::forUser($user)->allows('update', $found)) {
            return $this->error('Anda tidak memiliki akses untuk mempublish unit ini.', 403);
        }

        $updated = $this->service->publish($course->id, $unit->id);

        return $this->success(['unit' => $updated], 'Unit berhasil dipublish.');
    }

    /**
     * @summary Unpublish Unit
     *
     * @description Meng-unpublish unit kompetensi. **Memerlukan role: Admin atau Superadmin (owner course)**
     *
     * @response 200 scenario="Success" {"success": true, "message": "Unit berhasil diunpublish.", "data": {"unit": {"id": 1, "status": "draft"}}}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk unpublish unit ini."}
     * @response 404 scenario="Not Found" {"success": false, "message": "Unit tidak ditemukan."}
     */
    public function unpublish(Course $course, Unit $unit)
    {
        $found = $this->service->show($course->id, $unit->id);
        if (! $found) {
            return $this->error('Unit tidak ditemukan.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! Gate::forUser($user)->allows('update', $found)) {
            return $this->error('Anda tidak memiliki akses untuk unpublish unit ini.', 403);
        }

        $updated = $this->service->unpublish($course->id, $unit->id);

        return $this->success(['unit' => $updated], 'Unit berhasil diunpublish.');
    }
}
