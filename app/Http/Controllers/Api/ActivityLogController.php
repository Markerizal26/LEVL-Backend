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
  use ApiResponse;

  public function __construct(private ActivityLogService $service) {}

  /**
   * Daftar Log Aktivitas
   *
   * @authenticated
   */
  public function index(Request $request): JsonResponse
  {
    $perPage = max(1, min((int) $request->input("per_page", 15), 100));
    $activities = $this->service->paginate($perPage);

    return $this->paginateResponse($activities, "Daftar activity log berhasil diambil");
  }

  /**
   * Detail Log Aktivitas
   *
   * @authenticated
   */
  public function show(int $id): JsonResponse
  {
    $activity = $this->service->find($id);

    if (!$activity) {
      return $this->notFound("Activity log tidak ditemukan");
    }

    return $this->success($activity, "Detail activity log berhasil diambil");
  }
}
