<?php

namespace Modules\Content\Http\Controllers;

use App\Contracts\Services\ContentServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Http\Requests\CreateAnnouncementRequest;
use Modules\Content\Http\Requests\ScheduleContentRequest;
use Modules\Content\Http\Requests\UpdateContentRequest;
use Modules\Content\Models\Announcement;

/**
 * @tags Konten & Berita
 */
class AnnouncementController extends Controller
{
    use ApiResponse;

    protected ContentServiceInterface $contentService;

    public function __construct(ContentServiceInterface $contentService)
    {
        $this->contentService = $contentService;
    }

    /**
     * Daftar Pengumuman
     *
     * Mengambil daftar pengumuman dengan pagination dan filter. Dapat difilter berdasarkan course, priority, dan status baca.
     *
     *
     * @summary Daftar Pengumuman
     * @allowedFilters course_id, priority, unread
     *
     * @queryParam course_id string Filter berdasarkan ID kursus. Example: 
     * @queryParam priority string Filter berdasarkan prioritas. Example: 
     * @queryParam unread string Filter berdasarkan status belum dibaca. Example: 
     *
     * @allowedSorts created_at, published_at
     *
     * @queryParam sort string Field untuk sorting. Allowed: created_at, published_at. Prefix dengan '-' untuk descending. Example: -created_at
     *
     * @filterEnum priority low|medium|high
     * @filterEnum unread true|false
     *
     * @queryParam page integer Nomor halaman. Example: 1
     * @queryParam per_page integer Jumlah item per halaman (default: 15). Example: 15
     *
     * @response 200 scenario="Success" {"success": true, "message": "Success", "data": [{"id": 1, "title": "Pengumuman Penting", "content": "Isi pengumuman...", "priority": "high", "status": "published", "author": {"id": 1, "name": "Admin"}}], "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 5}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */    
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $filters = [
            'course_id' => $request->input('course_id'),
            'priority' => $request->input('priority'),
            'unread' => $request->boolean('unread'),
            'per_page' => $request->input('per_page', 15),
        ];

        $announcements = $this->contentService->getAnnouncementsForUser($user, $filters);

        return $this->paginateResponse($announcements);
    }

    /**
     * Buat Pengumuman Baru
     *
     * Membuat pengumuman baru. Dapat langsung dipublish atau dijadwalkan. **Memerlukan role: Admin atau Instructor**
     *
     *
     * @summary Buat Pengumuman Baru
     * @response 201 scenario="Success" {"success": true, "message": "Pengumuman berhasil dibuat.", "data": {"announcement": {"id": 1, "title": "Pengumuman Baru", "status": "draft", "priority": "medium"}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk membuat pengumuman."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"title": ["Judul wajib diisi."]}}
     *
     * @authenticated
     */    
    public function store(CreateAnnouncementRequest $request): JsonResponse
    {
        $this->authorize('createAnnouncement', Announcement::class);

        try {
            $announcement = $this->contentService->createAnnouncement(
                $request->validated(),
                auth()->user()
            );

            // Auto-publish if status is published
            if ($request->input('status') === 'published') {
                $this->contentService->publishContent($announcement);
            }

            // Auto-schedule if scheduled_at is provided
            if ($request->filled('scheduled_at')) {
                $this->contentService->scheduleContent(
                    $announcement,
                    \Carbon\Carbon::parse($request->input('scheduled_at'))
                );
            }

            return $this->created(
                ['announcement' => $announcement->load(['author', 'course'])],
                'Pengumuman berhasil dibuat.'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Detail Pengumuman
     *
     * Mengambil detail pengumuman. Otomatis menandai sebagai dibaca dan menambah view count.
     *
     *
     * @summary Detail Pengumuman
     * @response 200 scenario="Success" {"success": true, "data": {"announcement": {"id": 1, "title": "Pengumuman Penting", "content": "Isi lengkap...", "priority": "high", "author": {"id": 1, "name": "Admin"}, "course": null, "revisions": []}}}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melihat pengumuman ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Pengumuman tidak ditemukan."}
     *
     * @authenticated
     */    
    public function show(int $id): JsonResponse
    {
        $announcement = Announcement::with(['author', 'course', 'revisions.editor'])
            ->findOrFail($id);

        $this->authorize('view', $announcement);

        // Mark as read by current user
        $this->contentService->markAsRead($announcement, auth()->user());

        // Increment views
        $this->contentService->incrementViews($announcement);

        return $this->success(['announcement' => $announcement]);
    }

    /**
     * Perbarui Pengumuman
     *
     * Memperbarui pengumuman. **Memerlukan role: Admin atau Instructor (author)**
     *
     *
     * @summary Perbarui Pengumuman
     * @response 200 scenario="Success" {"success": true, "message": "Pengumuman berhasil diperbarui.", "data": {"announcement": {"id": 1, "title": "Pengumuman Updated"}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk memperbarui pengumuman ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Pengumuman tidak ditemukan."}
     *
     * @authenticated
     */    
    public function update(UpdateContentRequest $request, int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->authorize('update', $announcement);

        try {
            $announcement = $this->contentService->updateAnnouncement(
                $announcement,
                $request->validated(),
                auth()->user()
            );

            return $this->success(
                ['announcement' => $announcement->load(['author', 'course'])],
                'Pengumuman berhasil diperbarui.'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Hapus Pengumuman
     *
     * Menghapus pengumuman. **Memerlukan role: Admin atau Instructor (author)**
     *
     *
     * @summary Hapus Pengumuman
     * @response 200 scenario="Success" {"success":true,"message":"Pengumuman berhasil dihapus.","data":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menghapus pengumuman ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Pengumuman tidak ditemukan."}
     *
     * @authenticated
     */    
    public function destroy(int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->authorize('delete', $announcement);

        $this->contentService->deleteContent($announcement, auth()->user());

        return $this->success([], 'Pengumuman berhasil dihapus.');
    }

    /**
     * Publikasikan Pengumuman
     *
     * Mempublikasikan pengumuman agar dapat dilihat oleh target audience. **Memerlukan role: Admin atau Instructor (author)**
     *
     *
     * @summary Publikasikan Pengumuman
     * @response 200 scenario="Success" {"success": true, "message": "Pengumuman berhasil dipublikasikan.", "data": {"announcement": {"id": 1, "status": "published", "published_at": "2024-01-15T10:00:00Z"}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mempublikasikan pengumuman ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Pengumuman tidak ditemukan."}
     *
     * @authenticated
     */    
    public function publish(int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->authorize('publish', $announcement);

        $this->contentService->publishContent($announcement);

        return $this->success(
            ['announcement' => $announcement->fresh()],
            'Pengumuman berhasil dipublikasikan.'
        );
    }

    /**
     * Jadwalkan Pengumuman
     *
     * Menjadwalkan pengumuman untuk dipublikasikan pada waktu tertentu. **Memerlukan role: Admin atau Instructor (author)**
     *
     *
     * @summary Jadwalkan Pengumuman
     * @response 200 scenario="Success" {"success": true, "message": "Pengumuman berhasil dijadwalkan.", "data": {"announcement": {"id": 1, "status": "scheduled", "scheduled_at": "2024-01-20T10:00:00Z"}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menjadwalkan pengumuman ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Pengumuman tidak ditemukan."}
     * @response 422 scenario="Invalid Date" {"success":false,"message":"Waktu publikasi harus di masa depan."}
     *
     * @authenticated
     */    
    public function schedule(ScheduleContentRequest $request, int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->authorize('schedule', $announcement);

        try {
            $this->contentService->scheduleContent(
                $announcement,
                \Carbon\Carbon::parse($request->input('scheduled_at'))
            );

            return $this->success(
                ['announcement' => $announcement->fresh()],
                'Pengumuman berhasil dijadwalkan.'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Tandai Pengumuman Dibaca
     *
     * Menandai pengumuman sebagai sudah dibaca oleh pengguna saat ini.
     *
     *
     * @summary Tandai Pengumuman Dibaca
     * @response 200 scenario="Success" {"success":true,"message":"Pengumuman ditandai sudah dibaca.","data":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Pengumuman tidak ditemukan."}
     *
     * @authenticated
     */    
    public function markAsRead(int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->contentService->markAsRead($announcement, auth()->user());

        return $this->success([], 'Pengumuman ditandai sudah dibaca.');
    }
}
