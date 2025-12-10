<?php

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Contracts\Services\ForumServiceInterface;
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
     * Daftar Thread Forum
     *
     * Mengambil daftar thread forum untuk scheme tertentu dengan filter pinned, resolved, dan closed.
     *
     *
     * @summary Daftar Thread Forum
     * @queryParam page integer Halaman pagination. Example: 1
     * @queryParam per_page integer Jumlah item per halaman. Default: 20. Example: 20
     * @queryParam filter[user_id] integer Filter berdasarkan pembuat thread. Example: 5
     * @queryParam filter[is_pinned] boolean Filter thread yang disematkan. Example: true
     * @queryParam filter[is_solved] boolean Filter thread yang sudah solved. Example: false
     * @queryParam filter[is_locked] boolean Filter thread yang dikunci. Example: false
     * @queryParam sort string Sorting field. Example: -created_at
     *
     * @allowedFilters user_id,is_pinned,is_solved,is_locked 
     *
     * @allowedSorts created_at,updated_at,replies_count,views_count
     *
     * @queryParam sort string Field untuk sorting. Allowed: created_at, updated_at, replies_count, views_count. Prefix dengan '-' untuk descending. Example: -created_at
     *
     * @response 200 scenario="Success" {"success": true, "data": [{"id": 1, "title": "Pertanyaan tentang Laravel", "content": "...", "is_pinned": false, "is_resolved": false, "is_closed": false, "replies_count": 5}], "meta": {"current_page": 1, "per_page": 20, "total": 50}}
     *
     * @authenticated
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
     * Buat Thread Baru
     *
     * Membuat thread diskusi baru pada scheme tertentu.
     *
     *
     * @summary Buat Thread Baru
     * @bodyParam title string required Judul thread. Example: Pertanyaan tentang Laravel
     * @bodyParam content string required Konten thread. Example: Bagaimana cara menggunakan Eloquent?
     *
     * @response 201 scenario="Created" {"success": true, "data": {"id": 1, "title": "Pertanyaan tentang Laravel", "content": "...", "user_id": 1, "scheme_id": 1}, "message": "Thread berhasil dibuat."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validation error", "errors": {"title": ["The title field is required."]}}
     * @response 500 scenario="Server Error" {"success":false,"message":"Gagal membuat thread."}
     *
     * @authenticated
     */    
    public function store(CreateThreadRequest $request, int $schemeId): JsonResponse
    {
        try {
            $thread = $this->forumService->createThread($schemeId, $request->validated(), auth()->id());

            return $this->created($thread, __('forums.thread_created'));
        } catch (\Exception $e) {
            return $this->error(__('forums.thread_creation_failed'), 500);
        }
    }

    /**
     * Detail Thread
     *
     * Mengambil detail thread beserta balasan-balasannya.
     *
     *
     * @summary Detail Thread
     * @response 200 scenario="Success" {"success": true, "data": {"id": 1, "title": "Pertanyaan tentang Laravel", "content": "...", "user": {"id": 1, "name": "John"}, "replies": []}}
     * @response 404 scenario="Not Found" {"success":false,"message":"Thread tidak ditemukan."}
     *
     * @authenticated
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
     * Perbarui Thread
     *
     * Memperbarui thread yang sudah ada. Hanya pemilik thread atau moderator yang dapat mengubah.
     *
     *
     * @summary Perbarui Thread
     * @response 200 scenario="Success" {"success": true, "data": {"id": 1, "title": "Judul Baru"}, "message": "Thread berhasil diperbarui."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mengubah thread ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Thread tidak ditemukan."}
     *
     * @authenticated
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
     * Hapus Thread
     *
     * Menghapus thread beserta semua balasannya. Hanya pemilik thread atau moderator yang dapat menghapus.
     *
     *
     * @summary Hapus Thread
     * @response 200 scenario="Success" {"success":true,"data":null,"message":"Thread berhasil dihapus."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menghapus thread ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Thread tidak ditemukan."}
     *
     * @authenticated
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
     * Sematkan Thread
     *
     * Menyematkan thread agar selalu muncul di atas daftar. Hanya moderator yang dapat menyematkan.
     *
     * Requires: Admin, Instructor, Superadmin
     *
     *
     * @summary Sematkan Thread
     * @response 200 scenario="Success" {"success": true, "data": {"id": 1, "is_pinned": true}, "message": "Thread berhasil disematkan."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menyematkan thread ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Thread tidak ditemukan."}
     *
     * @authenticated
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
     * Tutup Thread
     *
     * Menutup thread sehingga tidak bisa menerima balasan baru. Hanya moderator yang dapat menutup.
     *
     * Requires: Admin, Instructor, Superadmin
     *
     *
     * @summary Tutup Thread
     * @response 200 scenario="Success" {"success": true, "data": {"id": 1, "is_closed": true}, "message": "Thread berhasil ditutup."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menutup thread ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Thread tidak ditemukan."}
     *
     * @authenticated
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
     * Cari Thread
     *
     * Mencari thread berdasarkan kata kunci pada judul dan konten.
     *
     *
     * @summary Cari Thread
     * @queryParam search string required Kata kunci pencarian. Example: laravel
     *
     * @response 200 scenario="Success" {"success": true, "data": [{"id": 1, "title": "Pertanyaan tentang Laravel"}], "message": "Hasil pencarian berhasil diambil."}
     * @response 400 scenario="Bad Request" {"success":false,"message":"Query pencarian diperlukan."}
     *
     * @authenticated
     */    
    public function search(Request $request, int $schemeId): JsonResponse
    {
        $query = $request->input('search', '');

        if (empty($query)) {
            return $this->error(__('forums.search_query_required'), 400);
        }

        $threads = $this->forumService->searchThreads($query, $schemeId);

        return $this->success($threads, __('forums.search_results_retrieved'));
    }
}
