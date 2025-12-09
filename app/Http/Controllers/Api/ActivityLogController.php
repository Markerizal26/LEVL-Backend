<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Laporan & Statistik
 */
class ActivityLogController extends Controller
{
    public function __construct(
        private ActivityLogService $service
    ) {}

    /**
     * Daftar Log Aktivitas
     *
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, (int) $request->input('per_page', 15));
        $activities = $this->service->paginate($perPage);

        return ApiResponse::paginated($activities, 'Daftar activity log berhasil diambil');
    }

    /**
     * Detail Log Aktivitas
     *
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        $activity = $this->service->find($id);

        if (! $activity) {
            return ApiResponse::error('Activity log tidak ditemukan', 404);
        }

        return ApiResponse::success($activity, 'Detail activity log berhasil diambil');
    }
}
