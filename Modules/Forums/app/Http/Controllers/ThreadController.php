<?php

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Contracts\Services\ForumServiceInterface;
use Modules\Forums\Http\Requests\CreateThreadRequest;
use Modules\Forums\Http\Requests\UpdateThreadRequest;
use Modules\Forums\Models\Thread;
use Modules\Forums\Services\ModerationService;

/**
 * @tags Forum Diskusi
 */
class ThreadController extends Controller
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
     * @summary Daftar Thread Forum
     *
     * @description Mengambil daftar thread forum untuk scheme tertentu dengan filter pinned, resolved, dan closed.
     *
     * @queryParam pinned boolean Filter thread yang disematkan. Example: true
     * @queryParam resolved boolean Filter thread yang sudah resolved. Example: false
     * @queryParam closed boolean Filter thread yang sudah ditutup. Example: false
     * @queryParam per_page integer Jumlah item per halaman. Default: 20. Example: 20
     *
     * @response 200 {"success": true, "data": [{"id": 1, "title": "Pertanyaan tentang Laravel", "content": "...", "is_pinned": false, "is_resolved": false, "is_closed": false, "replies_count": 5}], "meta": {"current_page": 1, "per_page": 20, "total": 50}}
     */
    public function index(Request $request, int $schemeId): JsonResponse
    {
        $filters = [
            'pinned' => $request->boolean('pinned'),
            'resolved' => $request->boolean('resolved'),
            'closed' => $request->has('closed') ? $request->boolean('closed') : null,
            'per_page' => $request->input('per_page', 20),
        ];

        $threads = $this->forumService->getThreadsForScheme($schemeId, $filters);

        return $this->paginateResponse($threads, __('forums.threads_retrieved'));
    }

    /**
     * @summary Buat Thread Baru
     *
     * @description Membuat thread diskusi baru pada scheme tertentu.
     *
     * @response 201 {"success": true, "data": {"id": 1, "title": "Pertanyaan tentang Laravel", "content": "...", "user_id": 1, "scheme_id": 1}, "message": "Thread berhasil dibuat."}
     * @response 422 {"success": false, "message": "Validation error", "errors": {"title": ["The title field is required."]}}
     * @response 500 {"success": false, "message": "Gagal membuat thread."}
     */
    public function store(CreateThreadRequest $request, int $schemeId): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), ['scheme_id' => $schemeId]);
            $thread = $this->forumService->createThread($data, $request->user());

            return $this->created($thread, __('forums.thread_created'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @summary Detail Thread
     *
     * @description Mengambil detail thread beserta balasan dan informasi user.
     *
     * @response 200 {"success": true, "data": {"id": 1, "title": "Pertanyaan tentang Laravel", "content": "...", "user": {"id": 1, "name": "John"}, "replies": []}}
     * @response 404 {"success": false, "message": "Thread tidak ditemukan."}
     */
    public function show(int $schemeId, int $threadId): JsonResponse
    {
        $thread = $this->forumService->getThreadDetail($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        return $this->success($thread, __('forums.thread_retrieved'));
    }

    /**
     * @summary Perbarui Thread
     *
     * @description Memperbarui thread yang sudah ada. Hanya pemilik thread atau moderator yang dapat mengubah.
     *
     * @response 200 {"success": true, "data": {"id": 1, "title": "Judul Baru"}, "message": "Thread berhasil diperbarui."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk mengubah thread ini."}
     * @response 404 {"success": false, "message": "Thread tidak ditemukan."}
     */
    public function update(UpdateThreadRequest $request, int $schemeId, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('update', $thread);

        try {
            $updatedThread = $this->forumService->updateThread($thread, $request->validated());

            return $this->success($updatedThread, __('forums.thread_updated'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @summary Hapus Thread
     *
     * @description Menghapus thread beserta semua balasannya. Hanya pemilik thread atau moderator yang dapat menghapus.
     *
     * @response 200 {"success": true, "data": null, "message": "Thread berhasil dihapus."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk menghapus thread ini."}
     * @response 404 {"success": false, "message": "Thread tidak ditemukan."}
     */
    public function destroy(Request $request, int $schemeId, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('delete', $thread);

        try {
            $this->forumService->deleteThread($thread, $request->user());

            return $this->success(null, __('forums.thread_deleted'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @summary Sematkan Thread
     *
     * @description Menyematkan thread agar selalu muncul di atas daftar. Hanya moderator yang dapat menyematkan.
     *
     * Requires: Admin, Instructor, Superadmin
     *
     * @response 200 {"success": true, "data": {"id": 1, "is_pinned": true}, "message": "Thread berhasil disematkan."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk menyematkan thread ini."}
     * @response 404 {"success": false, "message": "Thread tidak ditemukan."}
     */
    public function pin(Request $request, int $schemeId, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('pin', $thread);

        try {
            $pinnedThread = $this->moderationService->pinThread($thread, $request->user());

            return $this->success($pinnedThread, __('forums.thread_pinned'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @summary Tutup Thread
     *
     * @description Menutup thread sehingga tidak bisa menerima balasan baru. Hanya moderator yang dapat menutup.
     *
     * Requires: Admin, Instructor, Superadmin
     *
     * @response 200 {"success": true, "data": {"id": 1, "is_closed": true}, "message": "Thread berhasil ditutup."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk menutup thread ini."}
     * @response 404 {"success": false, "message": "Thread tidak ditemukan."}
     */
    public function close(Request $request, int $schemeId, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('close', $thread);

        try {
            $closedThread = $this->moderationService->closeThread($thread, $request->user());

            return $this->success($closedThread, __('forums.thread_closed'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @summary Cari Thread
     *
     * @description Mencari thread berdasarkan kata kunci pada judul dan konten.
     *
     * @queryParam q string required Kata kunci pencarian. Example: laravel
     *
     * @response 200 {"success": true, "data": [{"id": 1, "title": "Pertanyaan tentang Laravel"}], "message": "Hasil pencarian berhasil diambil."}
     * @response 400 {"success": false, "message": "Query pencarian diperlukan."}
     */
    public function search(Request $request, int $schemeId): JsonResponse
    {
        $query = $request->input('q', '');

        if (empty($query)) {
            return $this->error(__('forums.search_query_required'), 400);
        }

        $threads = $this->forumService->searchThreads($query, $schemeId);

        return $this->success($threads, __('forums.search_results_retrieved'));
    }
}
