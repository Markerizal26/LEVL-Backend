<?php

namespace Modules\Search\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Search\Contracts\SearchServiceInterface;
use Modules\Search\Models\SearchHistory;

/**
 * @tags Pencarian
 */
class SearchController extends Controller
{
    protected SearchServiceInterface $searchService;

    public function __construct(SearchServiceInterface $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Cari Kursus
     *
     *
     * @summary Cari Kursus
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Search"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '') ?? '';

        // Build filters from request
        $filters = [];

        if ($request->has('category_id')) {
            $filters['category_id'] = is_array($request->input('category_id'))
                ? $request->input('category_id')
                : [$request->input('category_id')];
        }

        if ($request->has('level_tag')) {
            $filters['level_tag'] = is_array($request->input('level_tag'))
                ? $request->input('level_tag')
                : [$request->input('level_tag')];
        }

        if ($request->has('instructor_id')) {
            $filters['instructor_id'] = is_array($request->input('instructor_id'))
                ? $request->input('instructor_id')
                : [$request->input('instructor_id')];
        }

        if ($request->has('status')) {
            $filters['status'] = is_array($request->input('status'))
                ? $request->input('status')
                : [$request->input('status')];
        }

        // Add pagination parameters
        $filters['per_page'] = $request->input('per_page', 15);
        $filters['page'] = $request->input('page', 1);

        // Build sort options
        $sort = [
            'field' => $request->input('sort_by', 'relevance'),
            'direction' => $request->input('sort_direction', 'desc'),
        ];

        // Perform search
        $result = $this->searchService->search($query, $filters, $sort);

        // Save search history for authenticated users
        if (auth()->check() && ! empty(trim($query))) {
            $this->searchService->saveSearchHistory(
                auth()->user(),
                $query,
                $filters,
                $result->total
            );
        }

        // If no results found, provide suggestions
        $suggestions = [];
        if ($result->total === 0 && ! empty(trim($query))) {
            $suggestions = $this->searchService->getSuggestions($query, 5);
        }

        return response()->json([
            'success' => true,
            'data' => $result->items->items(),
            'meta' => [
                'query' => $result->query,
                'filters' => $result->filters,
                'sort' => $result->sort,
                'total' => $result->total,
                'execution_time' => $result->executionTime,
                'suggestions' => $suggestions,
            ],
            'pagination' => [
                'current_page' => $result->items->currentPage(),
                'per_page' => $result->items->perPage(),
                'total' => $result->items->total(),
                'last_page' => $result->items->lastPage(),
                'from' => $result->items->firstItem(),
                'to' => $result->items->lastItem(),
            ],
        ]);
    }

    /**
     * Saran Pencarian
     *
     *
     * @summary Saran Pencarian
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Search"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('query', '') ?? '';
        $limit = $request->input('limit', 10);

        $suggestions = $this->searchService->getSuggestions($query, $limit);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Riwayat Pencarian
     *
     *
     * @summary Riwayat Pencarian
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Search"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function getSearchHistory(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);

        $history = SearchHistory::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Hapus Riwayat Pencarian
     *
     *
     * @summary Hapus Riwayat Pencarian
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Search"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function clearSearchHistory(Request $request): JsonResponse
    {
        // If specific ID is provided, delete that entry
        if ($request->has('id')) {
            SearchHistory::where('user_id', auth()->id())
                ->where('id', $request->input('id'))
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Search history entry deleted successfully',
            ]);
        }

        // Otherwise, clear all history for the user
        SearchHistory::where('user_id', auth()->id())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Search history cleared successfully',
        ]);
    }
}
