<?php

namespace Modules\Content\Http\Controllers;

use App\Contracts\Services\ContentServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Http\Requests\CreateAnnouncementRequest;
use Modules\Content\Models\Announcement;
use Modules\Content\Repositories\AnnouncementRepository;
use Modules\Schemes\Models\Course;

/**
 * @tags Konten & Berita
 */
class CourseAnnouncementController extends Controller
{
    protected ContentServiceInterface $contentService;

    protected AnnouncementRepository $announcementRepository;

    public function __construct(
        ContentServiceInterface $contentService,
        AnnouncementRepository $announcementRepository
    ) {
        $this->contentService = $contentService;
        $this->announcementRepository = $announcementRepository;
    }

    /**
     * Daftar Pengumuman Kursus
     *
     *
     * @summary Daftar Pengumuman Kursus
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example CourseAnnouncement"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function index(Request $request, int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);

        $filters = [
            'per_page' => $request->input('per_page', 15),
        ];

        $announcements = $this->announcementRepository->getAnnouncementsForCourse($courseId, $filters);

        return response()->json([
            'status' => 'success',
            'data' => $announcements,
        ]);
    }

    /**
     * Buat Pengumuman Kursus Baru
     *
     *
     * @summary Buat Pengumuman Kursus Baru
     *
     * @response 201 scenario="Success" {"success":true,"message":"CourseAnnouncement berhasil dibuat.","data":{"id":1,"name":"New CourseAnnouncement"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function store(CreateAnnouncementRequest $request, int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);

        $this->authorize('createCourseAnnouncement', [Announcement::class, $courseId]);

        try {
            $data = $request->validated();
            $data['course_id'] = $courseId;
            $data['target_type'] = 'course';

            $announcement = $this->contentService->createAnnouncement($data, auth()->user());

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

            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman kursus berhasil dibuat.',
                'data' => $announcement->load(['author', 'course']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
