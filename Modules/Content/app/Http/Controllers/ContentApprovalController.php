<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Content\Contracts\ContentWorkflowServiceInterface;
use Modules\Content\Exceptions\InvalidTransitionException;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;

/**
 * @tags Konten & Berita
 */
class ContentApprovalController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ContentWorkflowServiceInterface $workflowService
    ) {}

    /**
     * Ajukan konten untuk review
     *
     *
     * @summary Ajukan konten untuk review
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentApproval"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function submit(Request $request, string $type, int $id): JsonResponse
    {
        $content = $this->findContent($type, $id);

        if (! $content) {
            return $this->notFound(__('content.not_found'));
        }

        try {
            $this->workflowService->submitForReview($content, Auth::user());

            return $this->success([
                'content' => $content->fresh(),
            ], __('content.submitted_for_review'));
        } catch (InvalidTransitionException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Setujui konten
     *
     *
     * @summary Setujui konten
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentApproval"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function approve(Request $request, string $type, int $id): JsonResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $content = $this->findContent($type, $id);

        if (! $content) {
            return $this->notFound(__('content.not_found'));
        }

        try {
            $this->workflowService->approve(
                $content,
                Auth::user(),
                $request->input('note')
            );

            return $this->success([
                'content' => $content->fresh(),
            ], __('content.approved'));
        } catch (InvalidTransitionException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Tolak konten
     *
     *
     * @summary Tolak konten
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentApproval"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function reject(Request $request, string $type, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $content = $this->findContent($type, $id);

        if (! $content) {
            return $this->notFound(__('content.not_found'));
        }

        try {
            $this->workflowService->reject(
                $content,
                Auth::user(),
                $request->input('reason')
            );

            return $this->success([
                'content' => $content->fresh(),
            ], __('content.rejected'));
        } catch (InvalidTransitionException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Mengambil konten yang menunggu review
     *
     *
     * @summary Mengambil konten yang menunggu review
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentApproval"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function pendingReview(Request $request): JsonResponse
    {
        $type = $request->query('type', 'all');

        $pendingContent = [];

        if ($type === 'all' || $type === 'news') {
            $news = News::whereIn('status', ['submitted', 'in_review'])
                ->with('author')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'news',
                        'title' => $item->title,
                        'status' => $item->status,
                        'author' => $item->author->name,
                        'created_at' => $item->created_at,
                    ];
                });

            $pendingContent = array_merge($pendingContent, $news->toArray());
        }

        if ($type === 'all' || $type === 'announcement') {
            $announcements = Announcement::whereIn('status', ['submitted', 'in_review'])
                ->with('author')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'announcement',
                        'title' => $item->title,
                        'status' => $item->status,
                        'author' => $item->author->name,
                        'created_at' => $item->created_at,
                    ];
                });

            $pendingContent = array_merge($pendingContent, $announcements->toArray());
        }

        usort($pendingContent, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        return $this->success([
            'pending_content' => $pendingContent,
            'count' => count($pendingContent),
        ], __('content.pending_review_retrieved'));
    }

    /**
     * Find content by type and ID.
     */
    private function findContent(string $type, int $id)
    {
        return match ($type) {
            'news' => News::find($id),
            'announcement' => Announcement::find($id),
            default => null,
        };
    }
}
