<?php

namespace Modules\Content\Http\Controllers;

use App\Contracts\Services\ContentServiceInterface;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\Traits\HandlesFiltering;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Contracts\Services\ContentStatisticsServiceInterface;
use Modules\Content\Contracts\Services\NewsServiceInterface;
use Modules\Content\Http\Requests\ScheduleContentRequest;
use Modules\Content\Http\Requests\UpdateContentRequest;

/**
 * @tags Konten & Berita
 */
class NewsController extends Controller
{
  use ApiResponse;
  use HandlesFiltering;

  public function __construct(
    private ContentServiceInterface $contentService,
    private ContentStatisticsServiceInterface $statisticsService,
    private NewsServiceInterface $newsService,
  ) {}

  /**
   * Daftar Berita
   *
   * Mengambil daftar berita dengan pagination dan filter.
   *
   * **Filter yang tersedia:**
   * - `filter[category_id]` (integer): Filter berdasarkan ID kategori
   * - `filter[tag_id]` (integer): Filter berdasarkan ID tag
   * - `filter[featured]` (boolean): Filter berita unggulan. Nilai: true, false
   * - `filter[date_from]` (string): Filter dari tanggal (format: Y-m-d)
   * - `filter[date_to]` (string): Filter sampai tanggal (format: Y-m-d)
   *
   * **Sorting:** Gunakan parameter `sort` dengan prefix `-` untuk descending. Nilai: created_at, published_at, views_count
   *
   * @summary Daftar Berita
   *
   * @queryParam filter[category_id] integer Filter berdasarkan ID kategori. Example: 5
   * @queryParam filter[tag_id] integer Filter berdasarkan ID tag. Example: 3
   * @queryParam filter[featured] boolean Filter berita unggulan. Nilai: true, false. Example: true
   * @queryParam filter[date_from] string Filter dari tanggal (format: Y-m-d). Example: 2025-01-01
   * @queryParam filter[date_to] string Filter sampai tanggal (format: Y-m-d). Example: 2025-12-31
   * @queryParam sort string Field untuk sorting. Prefix dengan '-' untuk descending. Example: -views_count
   * @queryParam page integer Nomor halaman. Default: 1. Example: 1
   * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
   *
   * @response 200 scenario="Success" {"success": true, "message": "Berhasil", "data": [{"id": 1, "title": "LSP Meluncurkan Program Sertifikasi Cloud Computing", "slug": "lsp-meluncurkan-program-sertifikasi-cloud-computing", "excerpt": "Dalam rangka memenuhi kebutuhan industri...", "is_featured": true, "views_count": 1250}], "meta": {"pagination": {"current_page": 1, "per_page": 15, "total": 75}}}
   *
   * @authenticated
   */
  public function index(Request $request): JsonResponse
  {
    $params = $this->extractFilterParams($request);

    // Add featured filter handling
    if ($request->has("filter.featured")) {
      $params["filter"]["featured"] = $request->boolean("filter.featured");
    }

    $news = $this->contentService->getNewsFeed($params);

    return $this->paginateResponse($news);
  }

  /**
   * Buat Berita Baru
   *
   * Membuat berita baru. Dapat langsung dipublish atau dijadwalkan. **Memerlukan role: Admin**
   *
   * @bodyParam title string required Judul berita. Example: Berita Terbaru Hari Ini
   * @bodyParam content string required Konten berita (HTML). Example: <p>Isi berita...</p>
   * @bodyParam excerpt string optional Ringkasan berita. Example: Ringkasan singkat
   * @bodyParam featured_image file optional Gambar utama (jpg, png, max 5MB). Example: image.jpg
   * @bodyParam status string optional Status (draft|published|scheduled). Example: draft
   * @bodyParam scheduled_at datetime optional Waktu publish (jika scheduled). Example: 2024-12-25 10:00:00
   * @bodyParam category_ids array optional Array ID kategori. Example: [1, 2]
   * @bodyParam tag_ids array optional Array ID tags. Example: [1, 2, 3]
   * @bodyParam is_featured boolean optional Tandai sebagai featured. Example: false
   *
   * @response 201 scenario="Success" {"success": true, "message": "Berita berhasil dibuat.", "data": {"id": 1, "title": "Berita Baru", "slug": "berita-baru", "status": "draft"}, "meta": null, "errors": null}
   * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi.", "data": null, "meta": null, "errors": null}
   * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk membuat berita.", "data": null, "meta": null, "errors": null}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "data": null, "meta": null, "errors": {}}
   *
   * @authenticated
   *
   * @role Admin
   */
  // public function store(StoreContentRequest $request): JsonResponse { ... } // Missing store method

  /**
   * Tampilkan Detail Berita
   *
   * Menampilkan detail berita berdasarkan slug.
   *
   * @response 200 scenario="Success" {"success": true, "message": "Berhasil", "data": {"id": 1, "title": "Berita Lengkap", "slug": "berita-lengkap", "content": "Isi berita...", "author": {"id": 1, "name": "Admin"}, "categories": [], "tags": []}, "meta": null, "errors": null}
   * @response 404 scenario="Not Found" {"success": false, "message": "Berita tidak ditemukan.", "data": null, "meta": null, "errors": null}
   *
   * @unauthenticated
   */
  // public function show(string $slug): JsonResponse { ... } // Missing show method

  /**
   * Perbarui Berita
   *
   * Memperbarui berita yang sudah ada. **Memerlukan role: Admin (author)**
   *
   *
   * @summary Perbarui Berita
   *
   * @bodyParam title string optional Judul berita. Example: Berita Terbaru Hari Ini
   * @bodyParam content string optional Konten berita (HTML). Example: <p>Isi berita...</p>
   * @bodyParam excerpt string optional Ringkasan berita. Example: Ringkasan singkat
   * @bodyParam featured_image file optional Gambar utama (jpg, png, max 5MB). Example: image.jpg
   * @bodyParam status string optional Status (draft|published|scheduled). Example: draft
   * @bodyParam scheduled_at datetime optional Waktu publish (jika scheduled). Example: 2024-12-25 10:00:00
   * @bodyParam category_ids array optional Array ID kategori. Example: [1, 2]
   * @bodyParam tag_ids array optional Array ID tags. Example: [1, 2, 3]
   * @bodyParam is_featured boolean optional Tandai sebagai featured. Example: false
   *
   * @response 200 scenario="Success" {"success": true, "message": "Berita berhasil diperbarui.", "data": {"id": 1, "title": "Berita Updated"}, "meta": null, "errors": null}
   * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi.", "data": null, "meta": null, "errors": null}
   * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk memperbarui berita ini.", "data": null, "meta": null, "errors": null}
   * @response 404 scenario="Not Found" {"success": false, "message": "Berita tidak ditemukan.", "data": null, "meta": null, "errors": null}
   *
   * @authenticated
   */
  public function update(UpdateContentRequest $request, string $slug): JsonResponse
  {
    $news = $this->newsService->findBySlug($slug);

    if (!$news) {
      throw new ResourceNotFoundException("Berita tidak ditemukan.");
    }

    $this->authorize("update", $news);

    $news = $this->contentService->updateNews($news, $request->validated(), auth()->user());

    return $this->success(NewsResource::make($news), "Berita berhasil diperbarui.");
  }

  /**
   * Hapus Berita
   *
   * Menghapus berita. **Memerlukan role: Admin (author)**
   *
   *
   * @summary Hapus Berita
   *
   * @response 200 scenario="Success" {"success": true, "message": "Berita berhasil dihapus.", "data": null, "meta": null, "errors": null}
   * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi.", "data": null, "meta": null, "errors": null}
   * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk menghapus berita ini.", "data": null, "meta": null, "errors": null}
   * @response 404 scenario="Not Found" {"success": false, "message": "Berita tidak ditemukan.", "data": null, "meta": null, "errors": null}
   *
   * @authenticated
   */
  public function destroy(string $slug): JsonResponse
  {
    $news = $this->newsService->findBySlug($slug);

    if (!$news) {
      throw new ResourceNotFoundException("Berita tidak ditemukan.");
    }

    $this->authorize("delete", $news);

    $this->contentService->deleteContent($news, auth()->user());

    return $this->success(null, __("messages.news.deleted"));
  }

  /**
   * Publikasikan Berita
   *
   * Mempublikasikan berita. **Memerlukan role: Admin (author)**
   *
   *
   * @summary Publikasikan Berita
   *
   * @response 200 scenario="Success" {"success": true, "message": "Berita berhasil dipublikasikan.", "data": {"id": 1, "status": "published", "published_at": "2024-01-15T10:00:00Z"}, "meta": null, "errors": null}
   * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi.", "data": null, "meta": null, "errors": null}
   * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk mempublikasikan berita ini.", "data": null, "meta": null, "errors": null}
   * @response 404 scenario="Not Found" {"success": false, "message": "Berita tidak ditemukan.", "data": null, "meta": null, "errors": null}
   *
   * @authenticated
   */
  public function publish(string $slug): JsonResponse
  {
    $news = $this->newsService->findBySlug($slug);

    if (!$news) {
      throw new ResourceNotFoundException("Berita tidak ditemukan.");
    }

    $this->authorize("publish", $news);

    $this->contentService->publishContent($news);

    return $this->success($news->fresh(), __("messages.news.published"));
  }

  /**
   * Jadwalkan Berita
   *
   * Menjadwalkan berita untuk dipublikasikan pada waktu tertentu. **Memerlukan role: Admin (author)**
   *
   *
   * @summary Jadwalkan Berita
   *
   * @response 200 scenario="Success" {"success": true, "message": "Berita berhasil dijadwalkan.", "data": {"id": 1, "status": "scheduled", "scheduled_at": "2024-01-20T10:00:00Z"}, "meta": null, "errors": null}
   * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi.", "data": null, "meta": null, "errors": null}
   * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk menjadwalkan berita ini.", "data": null, "meta": null, "errors": null}
   * @response 404 scenario="Not Found" {"success": false, "message": "Berita tidak ditemukan.", "data": null, "meta": null, "errors": null}
   * @response 422 scenario="Invalid Date" {"success": false, "message": "Waktu publikasi harus di masa depan.", "data": null, "meta": null, "errors": null}
   *
   * @authenticated
   */
  public function schedule(ScheduleContentRequest $request, string $slug): JsonResponse
  {
    $news = $this->newsService->findBySlug($slug);

    if (!$news) {
      throw new ResourceNotFoundException("Berita tidak ditemukan.");
    }

    $this->authorize("schedule", $news);

    $this->contentService->scheduleContent(
      $news,
      \Carbon\Carbon::parse($request->input("scheduled_at")),
    );

    return $this->success($news->fresh(), __("messages.news.scheduled"));
  }

  /**
   * Berita Trending
   *
   * Mengambil daftar berita trending berdasarkan views dan engagement.
   *
   *
   * @summary Berita Trending
   *
   * @queryParam limit integer Jumlah berita yang ditampilkan (default: 10). Example: 10
   *
   * @response 200 scenario="Success" {"success": true, "message": "Berhasil", "data": [{"id": 1, "title": "Berita Populer", "slug": "berita-populer", "views_count": 1500}], "meta": null, "errors": null}
   *
   * @authenticated
   */
  public function trending(Request $request): JsonResponse
  {
    $limit = $request->input("limit", 10);
    $trending = $this->statisticsService->getTrendingNews($limit);

    return $this->success($trending);
  }
}
