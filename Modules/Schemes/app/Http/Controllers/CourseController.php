<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use App\Support\Traits\HandlesMediaUploads;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Contracts\Services\CourseServiceInterface;
use Modules\Schemes\Http\Requests\CourseRequest;
use Modules\Schemes\Models\Course;

/**
 * @tags Skema & Kursus
 */
class CourseController extends Controller
{
    use ApiResponse, HandlesMediaUploads;

    public function __construct(
        private CourseServiceInterface $service,
    ) {}

    /**
     * Daftar Kursus
     *
     * Mengambil daftar kursus dengan pagination, filter, dan sorting. Jika filter status=published, hanya menampilkan kursus yang sudah dipublish.
     *
     *
     * @summary Daftar Kursus
     *
     * @queryParam search string Kata kunci pencarian (nama, deskripsi, code). Example: pemrograman
     * @queryParam page integer Nomor halaman. Example: 1
     * @queryParam per_page integer Jumlah item per halaman (default: 15). Example: 15
     * @queryParam filter[status] string Filter berdasarkan status (draft|published|archived). Example: published
     * @queryParam filter[level_tag] string Filter berdasarkan level tag (dasar|menengah|mahir). Example: dasar
     * @queryParam filter[type] string Filter berdasarkan tipe (okupasi|kluster). Example: okupasi
     * @queryParam filter[category_id] integer Filter berdasarkan ID kategori. Example: 1
     * @queryParam filter[tag] string Filter berdasarkan tag. Example: laravel
     * @queryParam sort string Field untuk sorting. Allowed: id, code, title, created_at, updated_at, published_at. Prefix dengan '-' untuk descending. Example: -created_at
     *
     * @allowedFilters status, level_tag, type, category_id, tag
     *
     * @allowedSorts id, code, title, created_at, updated_at, published_at
     *
     * @allowedIncludes tags, outcomes, units, instructor
     *
     * @response 200 scenario="Success" {"success": true, "message": "Success", "data": [{"id": 1, "code": "CS101", "title": "Pemrograman Dasar", "slug": "pemrograman-dasar", "status": "published", "level_tag": "dasar", "type": "okupasi"}], "meta": {"current_page": 1, "last_page": 5, "per_page": 15, "total": 75}, "links": {"first": "...", "last": "...", "prev": null, "next": "..."}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function index(Request $request)
    {
        $status = $request->input('filter.status');
        $perPage = max(1, (int) $request->query('per_page', 15));

        $paginator =
          $status === 'published'
            ? $this->service->listPublic($perPage)
            : $this->service->list($perPage);

        return $this->paginateResponse($paginator);
    }

    /**
     * Buat Kursus Baru
     *
     * Membuat kursus baru. Mendukung upload thumbnail dan banner. **Memerlukan role: Admin atau Instructor**
     *
     *
     * @summary Buat Kursus Baru
     *
     * @bodyParam code string required Kode unik kursus. Example: CS101
     * @bodyParam title string required Judul kursus. Example: Pemrograman Dasar
     * @bodyParam description string optional Deskripsi kursus. Example: Belajar pemrograman dari dasar
     * @bodyParam category_id integer required ID kategori. Example: 1
     * @bodyParam level_tag string required Level kursus (dasar|menengah|mahir). Example: dasar
     * @bodyParam type string required Tipe kursus (okupasi|kluster). Example: okupasi
     * @bodyParam thumbnail file optional Gambar thumbnail (jpg, png, max 2MB). Example: thumbnail.jpg
     * @bodyParam banner file optional Gambar banner (jpg, png, max 5MB). Example: banner.jpg
     * @bodyParam tags array optional Array ID tags. Example: [1, 2, 3]
     * @bodyParam outcomes array optional Array learning outcomes. Example: ["Outcome 1", "Outcome 2"]
     * @bodyParam enrollment_type string optional Tipe pendaftaran (auto_accept|manual_approval|key_based). Example: auto_accept
     *
     * @response 201 scenario="Success" {"success": true, "message": "Course berhasil dibuat.", "data": {"course": {"id": 1, "code": "CS101", "title": "Pemrograman Dasar", "slug": "pemrograman-dasar", "status": "draft"}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk membuat kursus."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"code": ["Kode sudah digunakan."]}}
     *
     * @authenticated
     *
     * @role Admin|Instructor
     */
    public function store(CourseRequest $request)
    {
        $data = $request->validated();

        /** @var \Modules\Auth\Models\User|null $actor */
        $actor = auth('api')->user();

        try {
            $course = $this->service->create($data, $actor);

            // Handle file uploads via Spatie Media Library
            $this->uploadMedia($course, $request, [
                'thumbnail' => 'thumbnail',
                'banner' => 'banner',
            ]);
        } catch (UniqueConstraintViolationException|QueryException $e) {
            return $this->handleCourseUniqueConstraint($e);
        }

        return $this->created(['course' => $course->fresh()], __('messages.courses.created'));
    }

    /**
     * Detail Kursus
     *
     * Mengambil detail kursus berdasarkan ID atau slug, termasuk tags dan outcomes.
     *
     *
     * @summary Detail Kursus
     *
     * @response 200 scenario="Success" {"success": true, "data": {"course": {"id": 1, "code": "CS101", "title": "Pemrograman Dasar", "slug": "pemrograman-dasar", "description": "Kursus dasar pemrograman", "status": "published", "tags": [{"id": 1, "name": "Programming"}], "outcomes": [{"id": 1, "description": "Memahami dasar pemrograman"}]}}}
     * @response 404 scenario="Not Found" {"success":false,"message":"Course tidak ditemukan."}
     *
     * @authenticated
     */
    public function show(Course $course)
    {
        return $this->success(['course' => $course->load(['tags', 'outcomes'])]);
    }

    /**
     * Perbarui Kursus
     *
     * Memperbarui data kursus. Mendukung upload thumbnail dan banner baru. **Memerlukan role: Admin atau Instructor (owner)**
     *
     *
     * @summary Perbarui Kursus
     *
     * @bodyParam code string optional Kode unik kursus. Example: CS101
     * @bodyParam title string optional Judul kursus. Example: Pemrograman Dasar Updated
     * @bodyParam description string optional Deskripsi kursus. Example: Belajar pemrograman dari dasar
     * @bodyParam category_id integer optional ID kategori. Example: 1
     * @bodyParam level_tag string optional Level kursus. Example: dasar
     * @bodyParam type string optional Tipe kursus. Example: okupasi
     * @bodyParam thumbnail file optional Gambar thumbnail baru. Example: thumbnail.jpg
     * @bodyParam banner file optional Gambar banner baru. Example: banner.jpg
     *
     * @response 200 scenario="Success" {"success": true, "message": "Course berhasil diperbarui.", "data": {"course": {"id": 1, "code": "CS101", "title": "Pemrograman Dasar Updated", "slug": "pemrograman-dasar", "status": "draft"}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk memperbarui kursus ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Course tidak ditemukan."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"code": ["Kode sudah digunakan."]}}
     *
     * @authenticated
     *
     * @role Admin|Instructor
     */
    public function update(CourseRequest $request, Course $course)
    {
        $this->authorize('update', $course);

        $data = $request->validated();

        try {
            $updatedCourse = $this->service->update($course->id, $data);

            // Handle file uploads via Spatie Media Library
            $this->uploadMedia($updatedCourse, $request, [
                'thumbnail' => 'thumbnail',
                'banner' => 'banner',
            ]);
        } catch (UniqueConstraintViolationException|QueryException $e) {
            return $this->handleCourseUniqueConstraint($e);
        }

        return $this->success(['course' => $updatedCourse->fresh()], __('messages.courses.updated'));
    }

    /**
     * Hapus Kursus
     *
     * Menghapus kursus. **Memerlukan role: Admin atau Instructor (owner)**
     *
     *
     * @summary Hapus Kursus
     *
     * @response 200 scenario="Success" {"success":true,"message":"Course berhasil dihapus.","data":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menghapus kursus ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Course tidak ditemukan."}
     *
     * @authenticated
     */
    public function destroy(Course $course)
    {
        $ok = $this->service->delete($course->id);

        return $this->success([], __('messages.courses.deleted'));
    }

    /**
     * Publish Kursus
     *
     * Mempublish kursus agar dapat diakses oleh student. **Memerlukan role: Admin atau Instructor (owner)**
     *
     *
     * @summary Publish Kursus
     *
     * @response 200 scenario="Success" {"success": true, "message": "Course berhasil dipublish.", "data": {"course": {"id": 1, "status": "published", "published_at": "2024-01-15T10:00:00Z"}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mempublish course ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Course tidak ditemukan."}
     *
     * @authenticated
     */
    public function publish(Course $course)
    {
        $this->authorize('update', $course);

        $updated = $this->service->publish($course->id);

        return $this->success(['course' => $updated], __('messages.courses.published'));
    }

    /**
     * Unpublish Kursus
     *
     * Meng-unpublish kursus sehingga tidak dapat diakses oleh student baru. **Memerlukan role: Admin atau Instructor (owner)**
     *
     *
     * @summary Unpublish Kursus
     *
     * @response 200 scenario="Success" {"success": true, "message": "Course berhasil diunpublish.", "data": {"course": {"id": 1, "status": "draft", "published_at": null}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk unpublish course ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Course tidak ditemukan."}
     *
     * @authenticated
     */
    public function unpublish(Course $course)
    {
        $this->authorize('update', $course);

        $updated = $this->service->unpublish($course->id);

        return $this->success(['course' => $updated], __('messages.courses.unpublished'));
    }

    /**
     * Buat Kunci Pendaftaran
     *
     * Generate a new random 12-character alphanumeric enrollment key for a course. The key will be uppercase and can be used by students to enroll in key-based courses. Only Admin/Instructor course owners can generate keys.
     *
     *
     * @summary Buat Kunci Pendaftaran
     *
     * @response 200 scenario="Success" {"success":true,"message":"Enrollment key berhasil digenerate.","data":{"enrollment_key":"ABC123XYZ789","course":{"id":1,"slug":"course-slug","enrollment_key":"ABC123XYZ789"}}}
     * @response 403 scenario="Unauthorized" {"success":false,"message":"Anda tidak memiliki akses untuk generate enrollment key course ini."}
     *
     * @authenticated
     */
    public function generateEnrollmentKey(Course $course)
    {
        $this->authorize('update', $course);

        // Generate random alphanumeric key (12 characters)
        $newKey = $this->service->generateEnrollmentKey(12);

        $updated = $this->service->update($course->id, [
            'enrollment_type' => 'key_based',
            'enrollment_key' => $newKey,
        ]);

        // Return the plain key to the user (the hash is stored in DB)
        return $this->success(
            [
                'course' => $updated,
                'enrollment_key' => $newKey,
            ],
            __('messages.courses.key_generated'),
        );
    }

    /**
     * Perbarui Pengaturan Pendaftaran
     *
     * Update enrollment type and key for a course. If enrollment_type is 'key_based' but no key provided, one will be auto-generated. If enrollment_type is not 'key_based', the key will be cleared.
     *
     *
     * @summary Perbarui Pengaturan Pendaftaran
     *
     * @bodyParam enrollment_type string required Enrollment type. Example: key_based Enum: auto_accept, key_based, approval
     * @bodyParam enrollment_key string optional Enrollment key (max 100 chars). Auto-generated if enrollment_type=key_based and not provided. Example: CUSTOMKEY123
     *
     * @response 200 scenario="Success with key" {"success":true,"message":"Enrollment settings berhasil diperbarui.","data":{"enrollment_key":"CUSTOMKEY123","course":{"enrollment_type":"key_based"}}}
     * @response 422 scenario="Validation Error" {"success":false,"errors":{"enrollment_type":["Jenis enrollment tidak valid."]}}
     *
     * Update the enrollment key and enrollment type for a course. Only Admin/Instructor can update keys for their courses.
     *
     * @authenticated
     */
    public function updateEnrollmentKey(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'enrollment_type' => ['required', 'in:auto_accept,key_based,approval'],
            'enrollment_key' => ['nullable', 'string', 'max:100'],
        ]);

        $plainKey = null;

        // If enrollment_type is key_based but no key provided, generate one
        if ($validated['enrollment_type'] === 'key_based' && empty($validated['enrollment_key'])) {
            $plainKey = $this->service->generateEnrollmentKey(12);
            $validated['enrollment_key'] = $plainKey;
        } elseif (
            $validated['enrollment_type'] === 'key_based' &&
            ! empty($validated['enrollment_key'])
        ) {
            $plainKey = $validated['enrollment_key'];
        }

        // If enrollment_type is not key_based, clear the key
        if ($validated['enrollment_type'] !== 'key_based') {
            $validated['enrollment_key'] = null;
        }

        $updated = $this->service->update($course->id, $validated);

        $response = ['course' => $updated];
        if ($validated['enrollment_type'] === 'key_based' && $plainKey !== null) {
            // Return the plain key to the user (the hash is stored in DB)
            $response['enrollment_key'] = $plainKey;
        }

        return $this->success($response, __('messages.courses.enrollment_settings_updated'));
    }

    /**
     * Hapus Kunci Pendaftaran
     *
     * Remove the enrollment key and automatically set enrollment type to 'auto_accept'. This makes the course publicly enrollable without requiring a key. Only Admin/Instructor course owners can remove keys.
     *
     *
     * @summary Hapus Kunci Pendaftaran
     *
     * @response 200 scenario="Success" {"success":true,"message":"Enrollment key berhasil dihapus dan enrollment type diubah ke auto_accept.","data":{"course":{"enrollment_type":"auto_accept","enrollment_key":null}}}
     * @response 403 scenario="Unauthorized" {"success":false,"message":"Anda tidak memiliki akses untuk remove enrollment key course ini."}
     *
     * @authenticated
     */
    public function removeEnrollmentKey(Course $course)
    {
        $this->authorize('update', $course);

        $updated = $this->service->update($course->id, [
            'enrollment_type' => 'auto_accept',
            'enrollment_key' => null,
        ]);

        return $this->success(
            ['course' => $updated],
            __('messages.courses.key_removed'),
        );
    }

    private function handleCourseUniqueConstraint(QueryException $e)
    {
        $message = $e->getMessage();

        $errors = [];
        if (str_contains($message, 'courses_code_unique')) {
            $errors['code'][] = __('messages.courses.code_exists');
        }
        if (str_contains($message, 'courses_slug_unique')) {
            $errors['slug'][] = __('messages.courses.slug_exists');
        }

        if (! empty($errors)) {
            return $this->validationError($errors);
        }

        return $this->validationError([
            'general' => [__('messages.courses.duplicate_data')],
        ]);
    }
}
