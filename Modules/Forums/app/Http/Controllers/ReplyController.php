<?php

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Contracts\Services\ForumServiceInterface;
use Modules\Forums\Http\Requests\CreateReplyRequest;
use Modules\Forums\Http\Requests\UpdateReplyRequest;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Forums\Services\ModerationService;

/**
 * @tags Forum Diskusi
 */
class ReplyController extends Controller
{
    use ApiResponse;

    protected ForumServiceInterface $forumService;

    protected ModerationService $moderationService;

    public function __construct(
        ForumServiceInterface $forumService,
        ModerationService $moderationService
    ) {
        $this->forumService = $forumService;
        $this->moderationService = $moderationService;
    }

    /**
     * @summary Buat Balasan Baru
     *
     * @description Membuat balasan baru pada thread. Dapat juga membuat nested reply dengan menyertakan parent_id.
     *
     * @response 201 {"success": true, "data": {"id": 1, "thread_id": 1, "content": "Ini balasan saya...", "user_id": 1, "parent_id": null}, "message": "Balasan berhasil dibuat."}
     * @response 400 {"success": false, "message": "Parent reply tidak valid."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk membalas thread ini."}
     * @response 404 {"success": false, "message": "Thread tidak ditemukan."}
     */
    public function store(CreateReplyRequest $request, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('create', [Reply::class, $thread]);

        try {
            $parentId = $request->input('parent_id');
            if ($parentId) {
                $parent = Reply::find($parentId);
                if (! $parent || $parent->thread_id != $threadId) {
                    return $this->error(__('forums.invalid_parent_reply'), 400);
                }
            }

            $reply = $this->forumService->createReply(
                $thread,
                $request->validated(),
                $request->user(),
                $parentId
            );

            return $this->created($reply, __('forums.reply_created'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @summary Perbarui Balasan
     *
     * @description Memperbarui balasan yang sudah ada. Hanya pemilik balasan yang dapat mengubah.
     *
     * @response 200 {"success": true, "data": {"id": 1, "content": "Konten yang diperbarui..."}, "message": "Balasan berhasil diperbarui."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk mengubah balasan ini."}
     * @response 404 {"success": false, "message": "Balasan tidak ditemukan."}
     */
    public function update(UpdateReplyRequest $request, int $replyId): JsonResponse
    {
        $reply = Reply::find($replyId);

        if (! $reply) {
            return $this->notFound(__('forums.reply_not_found'));
        }

        $this->authorize('update', $reply);

        try {
            $updatedReply = $this->forumService->updateReply($reply, $request->validated());

            return $this->success($updatedReply, __('forums.reply_updated'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @summary Hapus Balasan
     *
     * @description Menghapus balasan. Hanya pemilik balasan atau moderator yang dapat menghapus.
     *
     * @response 200 {"success": true, "data": null, "message": "Balasan berhasil dihapus."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk menghapus balasan ini."}
     * @response 404 {"success": false, "message": "Balasan tidak ditemukan."}
     */
    public function destroy(Request $request, int $replyId): JsonResponse
    {
        $reply = Reply::find($replyId);

        if (! $reply) {
            return $this->notFound(__('forums.reply_not_found'));
        }

        $this->authorize('delete', $reply);

        try {
            $this->forumService->deleteReply($reply, $request->user());

            return $this->success(null, __('forums.reply_deleted'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @summary Terima Balasan sebagai Jawaban
     *
     * @description Menandai balasan sebagai jawaban yang diterima. Hanya pemilik thread yang dapat menerima jawaban.
     *
     * @response 200 {"success": true, "data": {"id": 1, "is_accepted": true}, "message": "Balasan diterima sebagai jawaban."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk menerima balasan ini."}
     * @response 404 {"success": false, "message": "Balasan tidak ditemukan."}
     */
    public function accept(Request $request, int $replyId): JsonResponse
    {
        $reply = Reply::find($replyId);

        if (! $reply) {
            return $this->notFound(__('forums.reply_not_found'));
        }

        $this->authorize('markAsAccepted', $reply);

        try {
            $acceptedReply = $this->moderationService->markAsAcceptedAnswer($reply, $request->user());

            return $this->success($acceptedReply, __('forums.reply_accepted'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
