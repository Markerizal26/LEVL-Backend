<?php

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Repositories\ForumStatisticsRepository;

/**
 * @tags Forum Diskusi
 */
class ForumStatisticsController extends Controller
{
    use ApiResponse;

    protected ForumStatisticsRepository $statisticsRepository;

    public function __construct(ForumStatisticsRepository $statisticsRepository)
    {
        $this->statisticsRepository = $statisticsRepository;
    }

    /**
     * Statistik Forum per Scheme
     *
     * Mengambil statistik forum untuk scheme tertentu dalam periode waktu tertentu. Dapat juga mengambil statistik per user.
     *
     * Requires: Admin, Instructor, Superadmin
     *
     *
     * @summary Statistik Forum per Scheme
     * @queryParam period_start date Tanggal mulai periode. Default: awal bulan ini. Example: 2024-01-01
     * @queryParam period_end date Tanggal akhir periode. Default: akhir bulan ini. Example: 2024-01-31
     * @queryParam user_id integer ID user untuk statistik individual. Example: 1
     *
     * @response 200 scenario="Success" {"success": true, "data": {"total_threads": 50, "total_replies": 200, "active_users": 25, "resolved_threads": 30}, "message": "Statistik berhasil diambil."}
     *
     * @authenticated
     */    
    public function index(Request $request, int $schemeId): JsonResponse
    {
        $request->validate([
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $periodStart = $request->input('period_start')
            ? Carbon::parse($request->input('period_start'))
            : Carbon::now()->startOfMonth();

        $periodEnd = $request->input('period_end')
            ? Carbon::parse($request->input('period_end'))
            : Carbon::now()->endOfMonth();

        $userId = $request->input('user_id');

        try {
            if ($userId) {
                $statistics = $this->statisticsRepository->getUserStatistics(
                    $schemeId,
                    $userId,
                    $periodStart,
                    $periodEnd
                );

                if (! $statistics) {
                    $statistics = $this->statisticsRepository->updateUserStatistics(
                        $schemeId,
                        $userId,
                        $periodStart,
                        $periodEnd
                    );
                }
            } else {
                $statistics = $this->statisticsRepository->getSchemeStatistics(
                    $schemeId,
                    $periodStart,
                    $periodEnd
                );

                if (! $statistics) {
                    $statistics = $this->statisticsRepository->updateSchemeStatistics(
                        $schemeId,
                        $periodStart,
                        $periodEnd
                    );
                }
            }

            return $this->success($statistics, __('forums.statistics_retrieved'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Statistik Forum User
     *
     * Mengambil statistik forum untuk user yang sedang login dalam periode waktu tertentu.
     *
     *
     * @summary Statistik Forum User
     * @queryParam period_start date Tanggal mulai periode. Default: awal bulan ini. Example: 2024-01-01
     * @queryParam period_end date Tanggal akhir periode. Default: akhir bulan ini. Example: 2024-01-31
     *
     * @response 200 scenario="Success" {"success": true, "data": {"threads_created": 5, "replies_posted": 20, "reactions_received": 15, "accepted_answers": 3}, "message": "Statistik user berhasil diambil."}
     *
     * @authenticated
     */    
    public function userStats(Request $request, int $schemeId): JsonResponse
    {
        $request->validate([
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        $periodStart = $request->input('period_start')
            ? Carbon::parse($request->input('period_start'))
            : Carbon::now()->startOfMonth();

        $periodEnd = $request->input('period_end')
            ? Carbon::parse($request->input('period_end'))
            : Carbon::now()->endOfMonth();

        try {
            $statistics = $this->statisticsRepository->getUserStatistics(
                $schemeId,
                $request->user()->id,
                $periodStart,
                $periodEnd
            );

            if (! $statistics) {
                $statistics = $this->statisticsRepository->updateUserStatistics(
                    $schemeId,
                    $request->user()->id,
                    $periodStart,
                    $periodEnd
                );
            }

            return $this->success($statistics, __('forums.user_statistics_retrieved'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
