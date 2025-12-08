<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\CourseRequest;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Repositories\CourseRepository;
use Modules\Schemes\Services\CourseService;

/**
 * @tags Skema & Kursus
 */
class CourseController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CourseService $service,
        private CourseRepository $repository,
    ) {}

    /**
     * @summary Daftar Kursus
     *
     * @description Mengambil daftar kursus dengan pagination, filter, dan sorting. Jika filter status=published, hanya menampilkan kursus yang sudah dipublish.
     *
     * @allowedFilters filter[search], filter[status], filter[level_tag], filter[type], filter[category_id], filter[tag]
     *
     * @allowedSorts id, code, title, created_at, updated_at, published_at
     *
     * @allowedIncludes tags, outcomes, units, instructor
     *
     * @filterEnum status draft|published|archived
     * @filterEnum level_tag dasar|menengah|mahir
     * @filterEnum type okupasi|kluster
     *
     * @queryParam page integer Nomor halaman. Example: 1
     * @queryParam per_page integer Jumlah item per halaman (default: 15). Example: 15
     * @queryParam search string Kata kunci pencarian. Example: pemrograman
     *
     * @response 200 scenario="Success" {"success": true, "message": "Success", "data": [{"id": 1, "code": "CS101", "title": "Pemrograman Dasar", "slug": "pemrograman-dasar", "status": "published", "level_tag": "dasar", "type": "okupasi"}], "meta": {"current_page": 1, "last_page": 5, "per_page": 15, "total": 75}, "links": {"first": "...", "last": "...", "prev": null, "next": "..."}}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     */
    public function index(Request $request)
    {
        $status = $request->input('filter.status');
        $perPage = max(1, (int) $request->query('per_page', 15));

        $paginator = ($status === 'published')
            ? $this->service->listPublic($perPage)
            : $this->service->list($perPage);

        return $this->paginateResponse($paginator);
    }

    /**
     * @summary Buat Kursus Baru
     *
     * @description Membuat kursus baru. Mendukung upload thumbnail dan banner. **Memerlukan role: Admin atau Instructor**
     *
     * @response 201 scenario="Success" {"success": true, "message": "Course berhasil dibuat.", "data": {"course": {"id": 1, "code": "CS101", "title": "Pemrograman Dasar", "slug": "pemrograman-dasar", "status": "draft"}}}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk membuat kursus."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"code": ["Kode sudah digunakan."]}}
     */
    public function store(CourseRequest $request)
    {
        $data = $request->validated();

        /** @var \Modules\Auth\Models\User|null $actor */
        $actor = auth('api')->user();

        try {
            $course = $this->service->create($data, $actor);

            // Handle file uploads via Spatie Media Library
            if ($request->hasFile('thumbnail')) {
                $course->addMedia($request->file('thumbnail'))->toMediaCollection('thumbnail');
            }
            if ($request->hasFile('banner')) {
                $course->addMedia($request->file('banner'))->toMediaCollection('banner');
            }
        } catch (UniqueConstraintViolationException|QueryException $e) {
            return $this->handleCourseUniqueConstraint($e);
        }

        return $this->created(['course' => $course->fresh()], 'Course berhasil dibuat.');
    }

    /**
     * @summary Detail Kursus
     *
     * @description Mengambil detail kursus berdasarkan ID atau slug, termasuk tags dan outcomes.
     *
     * @response 200 scenario="Success" {"success": true, "data": {"course": {"id": 1, "code": "CS101", "title": "Pemrograman Dasar", "slug": "pemrograman-dasar", "description": "Kursus dasar pemrograman", "status": "published", "tags": [{"id": 1, "name": "Programming"}], "outcomes": [{"id": 1, "description": "Memahami dasar pemrograman"}]}}}
     * @response 404 scenario="Not Found" {"success": false, "message": "Course tidak ditemukan."}
     */
    public function show(Course $course)
    {
        return $this->success(['course' => $course->load(['tags', 'outcomes'])]);
    }

    /**
     * @summary Perbarui Kursus
     *
     * @description Memperbarui data kursus. Mendukung upload thumbnail dan banner baru. **Memerlukan role: Admin atau Instructor (owner)**
     *
     * @response 200 scenario="Success" {"success": true, "message": "Course berhasil diperbarui.", "data": {"course": {"id": 1, "code": "CS101", "title": "Pemrograman Dasar Updated", "slug": "pemrograman-dasar", "status": "draft"}}}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk memperbarui kursus ini."}
     * @response 404 scenario="Not Found" {"success": false, "message": "Course tidak ditemukan."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"code": ["Kode sudah digunakan."]}}
     */
    public function update(CourseRequest $request, Course $course)
    {
        $data = $request->validated();

        try {
            $updated = $this->service->update($course->id, $data);

            // Handle file uploads via Spatie Media Library
            if ($request->hasFile('thumbnail')) {
                $updated->clearMediaCollection('thumbnail');
                $updated->addMedia($request->file('thumbnail'))->toMediaCollection('thumbnail');
            }
            if ($request->hasFile('banner')) {
                $updated->clearMediaCollection('banner');
                $updated->addMedia($request->file('banner'))->toMediaCollection('banner');
            }
        } catch (UniqueConstraintViolationException|QueryException $e) {
            return $this->handleCourseUniqueConstraint($e);
        }

        return $this->success(['course' => $updated->fresh()], 'Course berhasil diperbarui.');
    }

    /**
     * @summary Hapus Kursus
     *
     * @description Menghapus kursus. **Memerlukan role: Admin atau Instructor (owner)**
     *
     * @response 200 scenario="Success" {"success": true, "message": "Course berhasil dihapus.", "data": []}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk menghapus kursus ini."}
     * @response 404 scenario="Not Found" {"success": false, "message": "Course tidak ditemukan."}
     */
    public function destroy(Course $course)
    {
        $ok = $this->service->delete($course->id);

        return $this->success([], 'Course berhasil dihapus.');
    }

    /**
     * @summary Publish Kursus
     *
     * @description Mempublish kursus agar dapat diakses oleh student. **Memerlukan role: Admin atau Instructor (owner)**
     *
     * @response 200 scenario="Success" {"success": true, "message": "Course berhasil dipublish.", "data": {"course": {"id": 1, "status": "published", "published_at": "2024-01-15T10:00:00Z"}}}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk mempublish course ini."}
     * @response 404 scenario="Not Found" {"success": false, "message": "Course tidak ditemukan."}
     */
    public function publish(Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! \Illuminate\Support\Facades\Gate::forUser($user)->allows('update', $course)) {
            return $this->error('Anda tidak memiliki akses untuk mempublish course ini.', 403);
        }

        $updated = $this->service->publish($course->id);

        return $this->success(['course' => $updated], 'Course berhasil dipublish.');
    }

    /**
     * @summary Unpublish Kursus
     *
     * @description Meng-unpublish kursus sehingga tidak dapat diakses oleh student baru. **Memerlukan role: Admin atau Instructor (owner)**
     *
     * @response 200 scenario="Success" {"success": true, "message": "Course berhasil diunpublish.", "data": {"course": {"id": 1, "status": "draft", "published_at": null}}}
     * @response 401 scenario="Unauthorized" {"success": false, "message": "Tidak terotorisasi."}
     * @response 403 scenario="Forbidden" {"success": false, "message": "Anda tidak memiliki akses untuk unpublish course ini."}
     * @response 404 scenario="Not Found" {"success": false, "message": "Course tidak ditemukan."}
     */
    public function unpublish(Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! \Illuminate\Support\Facades\Gate::forUser($user)->allows('update', $course)) {
            return $this->error('Anda tidak memiliki akses untuk unpublish course ini.', 403);
        }

        $updated = $this->service->unpublish($course->id);

        return $this->success(['course' => $updated], 'Course berhasil diunpublish.');
    }

    /**
     * Generate a new enrollment key for the course.
     *
     * @summary Buat Kunci Pendaftaran
     *
     * @description Generate a new random 12-character alphanumeric enrollment key for a course. The key will be uppercase and can be used by students to enroll in key-based courses. Only Admin/Instructor course owners can generate keys.
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Enrollment key berhasil digenerate.",
     *   "data": {
     *     "enrollment_key": "ABC123XYZ789",
     *     "course": { "id": 1, "slug": "course-slug", "enrollment_key": "ABC123XYZ789" }
     *   }
     * }
     * @response 403 scenario="Unauthorized" { "success": false, "message": "Anda tidak memiliki akses untuk generate enrollment key course ini." }
     */
    public function generateEnrollmentKey(Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! \Illuminate\Support\Facades\Gate::forUser($user)->allows('update', $course)) {
            return $this->error(
                'Anda tidak memiliki akses untuk generate enrollment key course ini.',
                403,
            );
        }

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
            'Enrollment key berhasil digenerate.',
        );
    }

    /**
     * Update enrollment key for the course.
     *
     * @summary Perbarui Pengaturan Pendaftaran
     *
     * @description Update enrollment type and key for a course. If enrollment_type is 'key_based' but no key provided, one will be auto-generated. If enrollment_type is not 'key_based', the key will be cleared.
     *
     * @bodyParam enrollment_type string required Enrollment type. Example: key_based Enum: auto_accept, key_based, approval
     * @bodyParam enrollment_key string optional Enrollment key (max 100 chars). Auto-generated if enrollment_type=key_based and not provided. Example: CUSTOMKEY123
     *
     * @response 200 scenario="Success with key" {
     *   "success": true,
     *   "message": "Enrollment settings berhasil diperbarui.",
     *   "data": {
     *     "enrollment_key": "CUSTOMKEY123",
     *     "course": { "enrollment_type": "key_based" }
     *   }
     * }
     * @response 422 scenario="Validation Error" { "success": false, "errors": { "enrollment_type": ["Jenis enrollment tidak valid."] } }
     *
     * @description Update the enrollment key and enrollment type for a course. Only Admin/Instructor can update keys for their courses.
     */
    public function updateEnrollmentKey(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! \Illuminate\Support\Facades\Gate::forUser($user)->allows('update', $course)) {
            return $this->error('Anda tidak memiliki akses untuk update enrollment key course ini.', 403);
        }

        $validated = $request->validate([
            'enrollment_type' => ['required', 'in:auto_accept,key_based,approval'],
            'enrollment_key' => ['nullable', 'string', 'max:100'],
        ]);

        $plainKey = null;

        // If enrollment_type is key_based but no key provided, generate one
        if ($validated['enrollment_type'] === 'key_based' && empty($validated['enrollment_key'])) {
            $plainKey = $this->service->generateEnrollmentKey(12);
            $validated['enrollment_key'] = $plainKey;
        } elseif ($validated['enrollment_type'] === 'key_based' && ! empty($validated['enrollment_key'])) {
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

        return $this->success($response, 'Enrollment settings berhasil diperbarui.');
    }

    /**
     * Remove enrollment key from the course.
     *
     * @summary Hapus Kunci Pendaftaran
     *
     * @description Remove the enrollment key and automatically set enrollment type to 'auto_accept'. This makes the course publicly enrollable without requiring a key. Only Admin/Instructor course owners can remove keys.
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Enrollment key berhasil dihapus dan enrollment type diubah ke auto_accept.",
     *   "data": { "course": { "enrollment_type": "auto_accept", "enrollment_key": null } }
     * }
     * @response 403 scenario="Unauthorized" { "success": false, "message": "Anda tidak memiliki akses untuk remove enrollment key course ini." }
     */
    public function removeEnrollmentKey(Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! \Illuminate\Support\Facades\Gate::forUser($user)->allows('update', $course)) {
            return $this->error('Anda tidak memiliki akses untuk remove enrollment key course ini.', 403);
        }

        $updated = $this->service->update($course->id, [
            'enrollment_type' => 'auto_accept',
            'enrollment_key' => null,
        ]);

        return $this->success(
            ['course' => $updated],
            'Enrollment key berhasil dihapus dan enrollment type diubah ke auto_accept.',
        );
    }

    private function handleCourseUniqueConstraint(QueryException $e)
    {
        $message = $e->getMessage();

        $errors = [];
        if (str_contains($message, 'courses_code_unique')) {
            $errors['code'][] = 'Kode sudah digunakan.';
        }
        if (str_contains($message, 'courses_slug_unique')) {
            $errors['slug'][] = 'Slug sudah digunakan.';
        }

        if (! empty($errors)) {
            return $this->validationError($errors);
        }

        return $this->validationError([
            'general' => ['Data duplikat. Periksa kembali isian Anda.'],
        ]);
    }
}
