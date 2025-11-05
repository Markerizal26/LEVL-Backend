<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Schemes\Http\Requests\ReorderUnitsRequest;
use Modules\Schemes\Http\Requests\UnitRequest;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Services\UnitService;

class UnitController extends Controller
{
    use ApiResponse;

    public function __construct(private UnitService $service) {}

    public function index(Request $request, int $course)
    {
        $params = $request->all();
        $paginator = $this->service->listByCourse($course, $params);

        return $this->paginateResponse($paginator);
    }

    public function store(UnitRequest $request, int $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $courseModel = Course::find($course);
        if (! $courseModel) {
            return $this->error('Course tidak ditemukan.', 404);
        }

        $authorized = false;
        if ($user->hasRole('super-admin')) {
            $authorized = true;
        } elseif ($user->hasRole('admin')) {
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
        $unit = $this->service->create($course, $data);

        return $this->created(['unit' => $unit], 'Unit berhasil dibuat.');
    }

    public function show(int $course, int $unit)
    {
        $found = $this->service->show($course, $unit);
        if (! $found) {
            return $this->error('Unit tidak ditemukan.', 404);
        }

        return $this->success(['unit' => $found]);
    }

    public function update(UnitRequest $request, int $course, int $unit)
    {
        $found = $this->service->show($course, $unit);
        if (! $found) {
            return $this->error('Unit tidak ditemukan.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! Gate::forUser($user)->allows('update', $found)) {
            return $this->error('Anda tidak memiliki akses untuk mengubah unit ini.', 403);
        }

        $data = $request->validated();
        $updated = $this->service->update($course, $unit, $data);

        return $this->success(['unit' => $updated], 'Unit berhasil diperbarui.');
    }

    public function destroy(int $course, int $unit)
    {
        $found = $this->service->show($course, $unit);
        if (! $found) {
            return $this->error('Unit tidak ditemukan.', 404);
        }

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! Gate::forUser($user)->allows('delete', $found)) {
            return $this->error('Anda tidak memiliki akses untuk menghapus unit ini.', 403);
        }

        $ok = $this->service->delete($course, $unit);

        return $this->success([], 'Unit berhasil dihapus.');
    }

    public function reorder(ReorderUnitsRequest $request, int $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $courseModel = Course::find($course);
        if (! $courseModel) {
            return $this->error('Course tidak ditemukan.', 404);
        }

        $authorized = false;
        if ($user->hasRole('super-admin')) {
            $authorized = true;
        } elseif ($user->hasRole('admin')) {
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
        $unitsInCourse = $this->service->getRepository()->getAllByCourse($course);
        $validUnitIds = $unitsInCourse->pluck('id')->toArray();
        $invalidIds = array_diff($unitIds, $validUnitIds);

        if (! empty($invalidIds)) {
            return $this->error('Beberapa unit tidak ditemukan di course ini.', 422);
        }

        $unitOrders = [];
        foreach ($unitIds as $index => $unitId) {
            $unitOrders[$unitId] = $index + 1;
        }
        $this->service->reorder($course, $unitOrders);

        return $this->success([], 'Urutan unit berhasil diperbarui.');
    }
}
