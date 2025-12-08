<?php

namespace Modules\Content\Http\Controllers;

use App\Contracts\Services\ContentServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Http\Requests\CreateNewsRequest;
use Modules\Content\Http\Requests\ScheduleContentRequest;
use Modules\Content\Http\Requests\UpdateContentRequest;
use Modules\Content\Models\News;
use Modules\Content\Services\ContentStatisticsService;

/**
 * @tags Konten & Berita
 */
class NewsController extends Controller
{
    protected ContentServiceInterface $contentService;

    protected ContentStatisticsService $statisticsService;

    public function __construct(
        ContentServiceInterface $contentService,
        ContentStatisticsService $statisticsService
    ) {
        $this->contentService = $contentService;
        $this->statisticsService = $statisticsService;
    }

    /**
     * @summary Daftar Berita
     *
     * @description Mengambil daftar berita dengan pagination dan filter. Dapat difilter berdasarkan kategori, tag, dan status featured.
     *
     * @allowedFilters category_id, tag_id, featured, date_from, date_to
     *
     * @allowedSorts created_at, published_at, views_count
     *
     * @filterEnum featured true|false
     *
     * @response 200 scenario="Success" {"status": "success", "data": {"data": [{"id": 1, "title": "Berita Terbaru", "slug": "berita-terbaru", "excerpt": "Ringkasan berita...", "featured": true}], "meta": {"current_page": 1, "total": 10}}}
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'category_id' => $request->input('category_id'),
            'tag_id' => $request->input('tag_id'),
            'featured' => $request->boolean('featured'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'per_page' => $request->input('per_page', 15),
        ];

        $news = $this->contentService->getNewsFeed($filters);

        return response()->json([
            'status' => 'success',
            'data' => $news,
        ]);
    }

    /**
     * @summary Buat Berita Baru
     *
     * @description Membuat berita baru. Dapat langsung dipublish atau dijadwalkan. **Memerlukan role: Admin**
     *
     * @response 201 scenario="Success" {"status": "success", "message": "Berita berhasil dibuat.", "data": {"id": 1, "title": "Berita Baru", "slug": "berita-baru", "status": "draft"}}
     * @response 401 scenario="Unauthorized" {"status": "error", "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"status": "error", "message": "Anda tidak memiliki akses untuk membuat berita."}
     * @response 422 scenario="Validation Error" {"status": "error", "message": "Validasi gagal."}
     */
    public function store(CreateNewsRequest $request): JsonResponse
    {
        $this->authorize('createNews', News::class);

        try {
            $news = $this->contentService->createNews(
                $request->validated(),
                auth()->user()
            );

            // Auto-publish if status is published
            if ($request->input('status') === 'published') {
                $this->contentService->publishContent($news);
            }

            // Auto-schedule if scheduled_at is provided
            if ($request->filled('scheduled_at')) {
                $this->contentService->scheduleContent(
                    $news,
                    \Carbon\Carbon::parse($request->input('scheduled_at'))
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berita berhasil dibuat.',
                'data' => $news->load(['author', 'categories', 'tags']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @summary Detail Berita
     *
     * @description Mengambil detail berita berdasarkan slug. Otomatis menambah view count.
     *
     * @response 200 scenario="Success" {"status": "success", "data": {"id": 1, "title": "Berita Lengkap", "slug": "berita-lengkap", "content": "Isi berita...", "author": {"id": 1, "name": "Admin"}, "categories": [], "tags": []}}
     * @response 404 scenario="Not Found" {"status": "error", "message": "Berita tidak ditemukan."}
     */
    public function show(string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)
            ->with(['author', 'categories', 'tags', 'revisions.editor'])
            ->firstOrFail();

        $this->authorize('view', $news);

        // Mark as read by current user if authenticated
        if (auth()->check()) {
            $this->contentService->markAsRead($news, auth()->user());
        }

        // Increment views
        $this->contentService->incrementViews($news);

        return response()->json([
            'status' => 'success',
            'data' => $news,
        ]);
    }

    /**
     * @summary Perbarui Berita
     *
     * @description Memperbarui berita. **Memerlukan role: Admin (author)**
     *
     * @response 200 scenario="Success" {"status": "success", "message": "Berita berhasil diperbarui.", "data": {"id": 1, "title": "Berita Updated"}}
     * @response 401 scenario="Unauthorized" {"status": "error", "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"status": "error", "message": "Anda tidak memiliki akses untuk memperbarui berita ini."}
     * @response 404 scenario="Not Found" {"status": "error", "message": "Berita tidak ditemukan."}
     */
    public function update(UpdateContentRequest $request, string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)->firstOrFail();

        $this->authorize('update', $news);

        try {
            $news = $this->contentService->updateNews(
                $news,
                $request->validated(),
                auth()->user()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Berita berhasil diperbarui.',
                'data' => $news->load(['author', 'categories', 'tags']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @summary Hapus Berita
     *
     * @description Menghapus berita. **Memerlukan role: Admin (author)**
     *
     * @response 200 scenario="Success" {"status": "success", "message": "Berita berhasil dihapus."}
     * @response 401 scenario="Unauthorized" {"status": "error", "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"status": "error", "message": "Anda tidak memiliki akses untuk menghapus berita ini."}
     * @response 404 scenario="Not Found" {"status": "error", "message": "Berita tidak ditemukan."}
     */
    public function destroy(string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)->firstOrFail();

        $this->authorize('delete', $news);

        $this->contentService->deleteContent($news, auth()->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Berita berhasil dihapus.',
        ]);
    }

    /**
     * @summary Publikasikan Berita
     *
     * @description Mempublikasikan berita. **Memerlukan role: Admin (author)**
     *
     * @response 200 scenario="Success" {"status": "success", "message": "Berita berhasil dipublikasikan.", "data": {"id": 1, "status": "published", "published_at": "2024-01-15T10:00:00Z"}}
     * @response 401 scenario="Unauthorized" {"status": "error", "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"status": "error", "message": "Anda tidak memiliki akses untuk mempublikasikan berita ini."}
     * @response 404 scenario="Not Found" {"status": "error", "message": "Berita tidak ditemukan."}
     */
    public function publish(string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)->firstOrFail();

        $this->authorize('publish', $news);

        $this->contentService->publishContent($news);

        return response()->json([
            'status' => 'success',
            'message' => 'Berita berhasil dipublikasikan.',
            'data' => $news->fresh(),
        ]);
    }

    /**
     * @summary Jadwalkan Berita
     *
     * @description Menjadwalkan berita untuk dipublikasikan pada waktu tertentu. **Memerlukan role: Admin (author)**
     *
     * @response 200 scenario="Success" {"status": "success", "message": "Berita berhasil dijadwalkan.", "data": {"id": 1, "status": "scheduled", "scheduled_at": "2024-01-20T10:00:00Z"}}
     * @response 401 scenario="Unauthorized" {"status": "error", "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"status": "error", "message": "Anda tidak memiliki akses untuk menjadwalkan berita ini."}
     * @response 404 scenario="Not Found" {"status": "error", "message": "Berita tidak ditemukan."}
     * @response 422 scenario="Invalid Date" {"status": "error", "message": "Waktu publikasi harus di masa depan."}
     */
    public function schedule(ScheduleContentRequest $request, string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)->firstOrFail();

        $this->authorize('schedule', $news);

        try {
            $this->contentService->scheduleContent(
                $news,
                \Carbon\Carbon::parse($request->input('scheduled_at'))
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Berita berhasil dijadwalkan.',
                'data' => $news->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @summary Berita Trending
     *
     * @description Mengambil daftar berita trending berdasarkan views dan engagement.
     *
     * @queryParam limit integer Jumlah berita yang ditampilkan (default: 10). Example: 10
     *
     * @response 200 scenario="Success" {"status": "success", "data": [{"id": 1, "title": "Berita Populer", "slug": "berita-populer", "views_count": 1500}]}
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
}
