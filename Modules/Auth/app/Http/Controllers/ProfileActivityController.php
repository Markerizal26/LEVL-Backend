<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Services\UserActivityService;

/**
 * @tags Profil Pengguna
 */
class ProfileActivityController extends Controller
{
    public function __construct(
        private UserActivityService $activityService
    ) {}

    /**
     * Riwayat Aktivitas Pengguna
     *
     * Mengambil riwayat aktivitas pengguna yang sedang login.
     *
     * **Filter yang tersedia:**
     * - `filter[type]` (string): Tipe aktivitas. Nilai: login, course_view, assignment_submit, forum_post
     * - `filter[start_date]` (string): Filter dari tanggal (format: Y-m-d)
     * - `filter[end_date]` (string): Filter sampai tanggal (format: Y-m-d)
     *
     * @summary Riwayat Aktivitas
     *
     * @queryParam filter[type] string Tipe aktivitas. Nilai: login, course_view, assignment_submit, forum_post. Example: login
     * @queryParam filter[start_date] string Filter dari tanggal (format: Y-m-d). Example: 2025-01-01
     * @queryParam filter[end_date] string Filter sampai tanggal (format: Y-m-d). Example: 2025-12-31
     * @queryParam per_page integer Jumlah item per halaman. Default: 20. Example: 20
     *
     * @response 200 scenario="Success" {"success":true,"message":"Berhasil","data":[{"id":1,"type":"login","description":"Login dari browser Chrome","created_at":"2025-01-15T10:00:00Z"}]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = [
            'type' => $request->input('filter.type'),
            'start_date' => $request->input('filter.start_date'),
            'end_date' => $request->input('filter.end_date'),
            'per_page' => $request->input('per_page', 20),
        ];

        $activities = $this->activityService->getActivities($user, $filters);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }
}
