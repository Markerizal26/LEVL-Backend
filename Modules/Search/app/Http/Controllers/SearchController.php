<?php

namespace Modules\Search\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\Traits\HandlesFiltering;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Search\Contracts\Repositories\SearchHistoryRepositoryInterface;
use Modules\Search\Contracts\Services\SearchServiceInterface;

/**
 * @tags Pencarian
 */
class SearchController extends Controller
{
    use ApiResponse;
    use HandlesFiltering;

    public function __construct(
        private SearchServiceInterface $searchService,
        private SearchHistoryRepositoryInterface $searchHistoryRepository
    ) {}

    /**
     * Cari Kursus
     *
     * Mencari kursus berdasarkan query dengan berbagai filter dan sorting options.
     *
     * @summary Cari Kursus
     *
     * @queryParam search string Kata kunci pencarian. Example: Laravel
     * @queryParam filter[category_id] integer|array Filter berdasarkan kategori (bisa multiple). Example: 1
     * @queryParam filter[level_tag] string|array Filter berdasarkan level (beginner|intermediate|advanced). Example: beginner
     * @queryParam filter[instructor_id] integer|array Filter berdasarkan instructor. Example: 5
     * @queryParam filter[status] string|array Filter berdasarkan status kursus. Example: published
     * @queryParam sort_by string Sort by field (relevance|created_at|title|rating). Default: relevance. Example: rating
     * @queryParam sort_direction string Sort direction (asc|desc). Default: desc. Example: desc
     * @queryParam page integer Halaman pagination. Default: 1. Example: 1
     * @queryParam per_page integer Items per halaman. Default: 15. Example: 15
     *
     * @response 200 scenario="Success" {"success":true,"data":[{"id":1,"title":"Kursus Laravel","slug":"kursus-laravel","category":{"id":1,"name":"Programming"},"instructor":{"id":5,"name":"John Doe"},"level_tag":"intermediate","rating":4.5}],"meta":{"query":"Laravel","filters":{"category_id":[1],"level_tag":["intermediate"]},"sort":{"field":"rating","direction":"desc"},"total":25,"per_page":15,"current_page":1,"last_page":2},"suggestions":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('search', '') ?? '';

        // Extract standardized filter params
        $params = $this->extractFilterParams($request);

        // Normalize array filters
        $filters = $params['filter'] ?? [];

        if (isset($filters['category_id']) && ! is_array($filters['category_id'])) {
            $filters['category_id'] = [$filters['category_id']];
        }

        if (isset($filters['level_tag']) && ! is_array($filters['level_tag'])) {
            $filters['level_tag'] = [$filters['level_tag']];
        }

        if (isset($filters['instructor_id']) && ! is_array($filters['instructor_id'])) {
            $filters['instructor_id'] = [$filters['instructor_id']];
        }

        if (isset($filters['status']) && ! is_array($filters['status'])) {
            $filters['status'] = [$filters['status']];
        }

        // Add pagination parameters
        $filters['per_page'] = $params['per_page'] ?? 15;
        $filters['page'] = $params['page'] ?? 1;

        // Build sort options - support both old (sort_by/sort_direction) and new (sort) formats
        $sort = [
            'field' => $request->input('sort_by') ?? (isset($params['sort']) ? ltrim($params['sort'], '-') : 'relevance'),
            'direction' => $request->input('sort_direction') ??
              (isset($params['sort']) && str_starts_with($params['sort'], '-') ? 'desc' : 'asc'),
        ];

        // Perform search
        $result = $this->searchService->search($query, $filters, $sort);

        // Save search history for authenticated users
        if (auth()->check() && ! empty(trim($query))) {
            $this->searchService->saveSearchHistory(auth()->user(), $query, $filters, $result->total);
        }

        // If no results found, provide suggestions
        $suggestions = [];
        if ($result->total === 0 && ! empty(trim($query))) {
            $suggestions = $this->searchService->getSuggestions($query, 5);
        }

        return $this->success(
            data: $result->items->items(),
            meta: [
                'query' => $result->query,
                'filters' => $result->filters,
                'sort' => $result->sort,
                'total' => $result->total,
                'execution_time' => $result->executionTime,
                'suggestions' => $suggestions,
                'pagination' => [
                    'current_page' => $result->items->currentPage(),
                    'per_page' => $result->items->perPage(),
                    'total' => $result->items->total(),
                    'last_page' => $result->items->lastPage(),
                    'from' => $result->items->firstItem(),
                    'to' => $result->items->lastItem(),
                    'has_next' => $result->items->hasMorePages(),
                    'has_prev' => $result->items->currentPage() > 1,
                ],
            ],
        );
    }

    /**
     * Saran Pencarian
     *
     * Mendapatkan suggestion/autocomplete untuk pencarian kursus.
     *
     * @summary Saran Pencarian
     *
     * @queryParam search string Kata kunci untuk autocomplete. Example: Lar
     * @queryParam limit integer Jumlah maksimal suggestions. Default: 10. Example: 5
     *
     * @response 200 scenario="Success" {"success":true,"data":["Laravel Basics","Laravel Advanced","Laravel API Development","Laravel Testing","Laravel Performance"]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('search', '') ?? '';
        $limit = $request->input('limit', 10);

        $suggestions = $this->searchService->getSuggestions($query, $limit);

        return $this->success(data: $suggestions);
    }

    /**
     * Riwayat Pencarian
     *
     * Mengambil riwayat pencarian user yang sedang login.
     *
     * @summary Riwayat Pencarian
     *
     * @queryParam limit integer Jumlah maksimal history yang ditampilkan. Default: 20. Example: 10
     *
     * @response 200 scenario="Success" {"success":true,"data":[{"id":1,"user_id":5,"query":"Laravel","filters":{"category_id":[1]},"result_count":25,"created_at":"2025-12-10T10:30:00Z"},{"id":2,"user_id":5,"query":"Vue.js","filters":{},"result_count":10,"created_at":"2025-12-09T15:20:00Z"}]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function getSearchHistory(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);

        $history = $this->searchHistoryRepository->findByUserId(auth()->id(), $limit);

        return $this->success(data: $history);
    }

    /**
     * Hapus Riwayat Pencarian
     *
     * Menghapus riwayat pencarian. Jika `id` diberikan, hapus entry tertentu. Jika tidak, hapus semua riwayat user.
     *
     * @summary Hapus Riwayat Pencarian
     *
     * @queryParam id integer optional ID history tertentu yang akan dihapus. Example: 1
     *
     * @response 200 scenario="Success - Specific Entry" {"success":true,"message":"Search history entry deleted successfully"}
     * @response 200 scenario="Success - All History" {"success":true,"message":"All search history cleared successfully"}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function clearSearchHistory(Request $request): JsonResponse
    {
        // If specific ID is provided, delete that entry
        if ($request->has('id')) {
            $this->searchHistoryRepository->deleteById($request->input('id'), auth()->id());

            return $this->success(message: __('messages.search.history_deleted'));
        }

        // Otherwise, clear all history for the user
        $this->searchHistoryRepository->deleteByUserId(auth()->id());

        return $this->success(message: __('messages.search.history_cleared'));
    }
}
