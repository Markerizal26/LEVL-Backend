<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Services\ProfileStatisticsService;

/**
 * @tags Profil Pengguna
 */
class ProfileStatisticsController extends Controller
{
    public function __construct(
        private ProfileStatisticsService $statisticsService
    ) {}

    /**
     * Ambil Statistik Profil
     *
     *
     * @summary Ambil Statistik Profil
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example ProfileStatistics"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $statistics = $this->statisticsService->getStatistics($user);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
}
