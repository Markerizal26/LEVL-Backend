<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;
use Modules\Content\Services\ContentStatisticsService;

/**
 * @tags Konten & Berita
 */
class ContentStatisticsController extends Controller
{
    protected ContentStatisticsService $statisticsService;

    public function __construct(ContentStatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Mengambil statistik konten keseluruhan
     *
     *
     * @summary Mengambil statistik konten keseluruhan
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example ContentStatistics"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', [Announcement::class]);

        $type = $request->input('type', 'all');
        $filters = [
            'course_id' => $request->input('course_id'),
            'category_id' => $request->input('category_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $data = [];

        if ($type === 'all' || $type === 'announcements') {
            $data['announcements'] = $this->statisticsService->getAllAnnouncementStatistics($filters);
        }

        if ($type === 'all' || $type === 'news') {
            $data['news'] = $this->statisticsService->getAllNewsStatistics($filters);
        }

        if ($type === 'all') {
            $data['dashboard'] = $this->statisticsService->getDashboardStatistics();
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Mengambil statistik pengumuman tertentu
     *
     *
     * @summary Mengambil statistik pengumuman tertentu
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentStatistics"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function showAnnouncement(int $id): JsonResponse
    {
        $this->authorize('viewStatistics', [Announcement::class]);

        $announcement = Announcement::findOrFail($id);
        $statistics = $this->statisticsService->getAnnouncementStatistics($announcement);

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }

    /**
     * Mengambil statistik berita tertentu
     *
     *
     * @summary Mengambil statistik berita tertentu
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentStatistics"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function showNews(string $slug): JsonResponse
    {
        $this->authorize('viewStatistics', [News::class]);

        $news = News::where('slug', $slug)->firstOrFail();
        $statistics = $this->statisticsService->getNewsStatistics($news);

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }

    /**
     * Mengambil berita trending
     *
     *
     * @summary Mengambil berita trending
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentStatistics"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function trending(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $trending = $this->statisticsService->getTrendingNews($limit);

        return response()->json([
            'status' => 'success',
            'data' => $trending,
        ]);
    }

    /**
     * Mengambil berita paling banyak dilihat
     *
     *
     * @summary Mengambil berita paling banyak dilihat
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentStatistics"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function mostViewed(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $limit = $request->input('limit', 10);

        $mostViewed = $this->statisticsService->getMostViewedNews($days, $limit);

        return response()->json([
            'status' => 'success',
            'data' => $mostViewed,
        ]);
    }
}
