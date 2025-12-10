<?php

namespace Modules\Content\Http\Controllers;

use App\Contracts\Services\ContentServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Konten & Berita
 */
class SearchController extends Controller
{
    protected ContentServiceInterface $contentService;

    public function __construct(ContentServiceInterface $contentService)
    {
        $this->contentService = $contentService;
    }

    /**
     * Pencarian Konten
     *
     * Mencari konten (berita dan pengumuman) berdasarkan kata kunci.
     *
     * **Filter yang tersedia:**
     * - `filter[type]` (string): Tipe konten. Nilai: all, news, announcements
     * - `filter[category_id]` (integer): Filter berdasarkan ID kategori
     * - `filter[date_from]` (string): Filter dari tanggal (format: Y-m-d)
     * - `filter[date_to]` (string): Filter sampai tanggal (format: Y-m-d)
     *
     * @summary Pencarian Konten
     *
     * @queryParam search string required Kata kunci pencarian (minimal 2 karakter). Example: sertifikasi
     * @queryParam filter[type] string Tipe konten. Nilai: all, news, announcements. Example: all
     * @queryParam filter[category_id] integer Filter berdasarkan ID kategori. Example: 5
     * @queryParam filter[date_from] string Filter dari tanggal (format: Y-m-d). Example: 2025-01-01
     * @queryParam filter[date_to] string Filter sampai tanggal (format: Y-m-d). Example: 2025-12-31
     * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
     *
     * @response 200 scenario="Success" {"success":true,"message":"Berhasil","data":{"news":[{"id":1,"title":"Sertifikasi Cloud Computing"}],"announcements":[{"id":1,"title":"Jadwal Sertifikasi"}]}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Kata kunci minimal 2 karakter."}
     *
     * @authenticated
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:2',
            'filter.type' => 'nullable|in:all,news,announcements',
        ]);

        $query = $request->input('search');
        $type = $request->input('filter.type', 'all');

        $filters = [
            'category_id' => $request->input('filter.category_id'),
            'date_from' => $request->input('filter.date_from'),
            'date_to' => $request->input('filter.date_to'),
            'per_page' => $request->input('per_page', 15),
        ];

        $results = $this->contentService->searchContent($query, $type, $filters);

        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);
    }
}
