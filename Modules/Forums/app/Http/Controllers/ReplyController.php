<?php

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Contracts\Services\ForumServiceInterface;
use Modules\Forums\Contracts\Services\ModerationServiceInterface;
use Modules\Forums\Http\Requests\CreateReplyRequest;
use Modules\Forums\Http\Requests\UpdateReplyRequest;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Forums\Repositories\ReplyRepository;
use Modules\Forums\Services\ModerationService;

/**
 * @tags Forum Diskusi
 */
class ReplyController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ForumServiceInterface $forumService,
        private ModerationService $moderationService,
        private ReplyRepository $replyRepository
    ) {}

    /**
     * Buat Balasan Baru
     *
     * Membuat balasan baru pada thread. Dapat juga membuat nested reply dengan menyertakan parent_id.
     *
     *
     * @summary Buat Balasan Baru
     *
     * @response 201 scenario="Created" {"success": true, "data": {"id": 1, "thread_id": 1, "content": "Ini balasan saya...", "user_id": 1, "parent_id": null}, "message": "Balasan berhasil dibuat."}
     * @response 400 scenario="Bad Request" {"success":false,"message":"Parent reply tidak valid."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk membalas thread ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Thread tidak ditemukan."}
     *
     * @authenticated
     */
    public function store(CreateReplyRequest $request, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('create', [Reply::class, $thread]);

        $parentId = $request->input('parent_id');
        if ($parentId) {
            $parent = $this->replyRepository->find($parentId);
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
    }

    /**
     * Perbarui Balasan
     *
     * Memperbarui balasan yang sudah ada. Hanya pemilik balasan yang dapat mengubah.
     *
     *
     * @summary Perbarui Balasan
     *
     * @response 200 scenario="Success" {"success": true, "data": {"id": 1, "content": "Konten yang diperbarui..."}, "message": "Balasan berhasil diperbarui."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mengubah balasan ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Balasan tidak ditemukan."}
     *
     * @authenticated
     */
    public function update(UpdateReplyRequest $request, int $replyId): JsonResponse
    {
        $reply = $this->replyRepository->find($replyId);

        if (! $reply) {
            return $this->notFound(__('forums.reply_not_found'));
        }

        $this->authorize('update', $reply);

        $updatedReply = $this->forumService->updateReply($reply, $request->validated());

        return $this->success($updatedReply, __('forums.reply_updated'));
    }

    /**
     * Hapus Balasan
     *
     * Menghapus balasan. Hanya pemilik balasan atau moderator yang dapat menghapus.
     *
     *
     * @summary Hapus Balasan
     *
     * @response 200 scenario="Success" {"success":true,"data":null,"message":"Balasan berhasil dihapus."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menghapus balasan ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Balasan tidak ditemukan."}
     *
     * @authenticated
     */
    public function destroy(Request $request, int $replyId): JsonResponse
    {
        $reply = $this->replyRepository->find($replyId);

        if (! $reply) {
            return $this->notFound(__('forums.reply_not_found'));
        }

        $this->authorize('delete', $reply);

        $this->forumService->deleteReply($reply, $request->user());

        return $this->success(null, __('forums.reply_deleted'));
    }

    /**
     * Terima Balasan sebagai Jawaban
     *
     * Menandai balasan sebagai jawaban yang diterima. Hanya pemilik thread yang dapat menerima jawaban.
     *
     *
     * @summary Terima Balasan sebagai Jawaban
     *
     * @response 200 scenario="Success" {"success": true, "data": {"id": 1, "is_accepted": true}, "message": "Balasan diterima sebagai jawaban."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menerima balasan ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Balasan tidak ditemukan."}
     *
     * @authenticated
     */
    public function accept(Request $request, int $replyId): JsonResponse
    {
        $reply = $this->replyRepository->find($replyId);

        if (! $reply) {
            return $this->notFound(__('forums.reply_not_found'));
        }

        $this->authorize('markAsAccepted', $reply);

        $acceptedReply = $this->moderationService->markAsAcceptedAnswer($reply, $request->user());

        return $this->success($acceptedReply, __('forums.reply_accepted'));
    }
}
