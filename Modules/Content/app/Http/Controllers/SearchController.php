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
     * Search content (news and announcements).
     *
     *
     * @summary Search content (news and announcements).
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Search"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'type' => 'nullable|in:all,news,announcements',
        ]);

        $query = $request->input('q');
        $type = $request->input('type', 'all');

        $filters = [
            'category_id' => $request->input('category_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'per_page' => $request->input('per_page', 15),
        ];

        $results = $this->contentService->searchContent($query, $type, $filters);

        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);
    }
}
