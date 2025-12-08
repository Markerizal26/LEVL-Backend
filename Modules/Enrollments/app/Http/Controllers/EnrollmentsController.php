<?php

namespace Modules\Enrollments\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Enrollments\DTOs\CreateEnrollmentDTO;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Services\EnrollmentService;
use Modules\Schemes\Models\Course;

/**
 * @tags Pendaftaran Kursus
 */
class EnrollmentsController extends Controller
{
    use ApiResponse;

    public function __construct(private EnrollmentService $service) {}

    /**
     * @summary Daftar Semua Pendaftaran (Superadmin)
     *
     * @description Mengambil daftar semua enrollment di sistem. Hanya Superadmin yang dapat mengakses endpoint ini.
     *
     * Requires: Superadmin
     *
     * @response 200 {"success": true, "data": {"enrollments": []}}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk melihat seluruh enrollment."}
     * @response 501 {"success": false, "message": "Endpoint tidak tersedia untuk saat ini."}
     */
    public function index(Request $request)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if (! $user->hasRole('Superadmin')) {
            return $this->error('Anda tidak memiliki akses untuk melihat seluruh enrollment.', 403);
        }

        // Note: paginate method needs to be added if used
        return $this->error('Endpoint tidak tersedia untuk saat ini.', 501);
    }

    /**
     * @summary Daftar Pendaftaran per Kursus
     *
     * @description Mengambil daftar enrollment untuk kursus tertentu. Hanya instructor atau admin kursus yang dapat mengakses.
     *
     * Requires: Admin, Instructor (course owner), Superadmin
     *
     * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
     *
     * @response 200 {"success": true, "data": {"enrollments": [{"id": 1, "user_id": 1, "course_id": 1, "status": "active", "user": {"id": 1, "name": "John Doe"}}]}, "meta": {"current_page": 1, "per_page": 15, "total": 50}}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk melihat enrollment course ini."}
     */
    public function indexByCourse(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if (! $this->userCanManageCourse($user, $course)) {
            return $this->error('Anda tidak memiliki akses untuk melihat enrollment course ini.', 403);
        }

        $perPage = max(1, (int) $request->query('per_page', 15));
        $paginator = $this->service->paginateByCourse($course->id, $perPage);

        return $this->paginateResponse($paginator, 'Daftar enrollment course berhasil diambil.');
    }

    /**
     * @summary Daftar Pendaftaran yang Dikelola
     *
     * @description Mengambil daftar enrollment dari kursus yang dikelola oleh user. Admin/Instructor melihat enrollment dari kursus mereka, Superadmin melihat semua.
     *
     * Requires: Admin, Instructor, Superadmin
     *
     * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
     * @queryParam filter[course_slug] string Filter berdasarkan slug kursus. Example: belajar-laravel
     *
     * @response 200 {"success": true, "data": {"enrollments": [{"id": 1, "user_id": 1, "course_id": 1, "status": "active"}]}, "meta": {"current_page": 1, "per_page": 15, "total": 50}}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk melihat enrollment ini."}
     * @response 404 {"success": false, "message": "Course tidak ditemukan atau tidak berada di bawah pengelolaan Anda."}
     */
    public function indexManaged(Request $request)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if ($user->hasRole('Superadmin')) {
            return $this->index($request);
        }

        if (! $user->hasRole('Admin') && ! $user->hasRole('Instructor')) {
            return $this->error('Anda tidak memiliki akses untuk melihat enrollment ini.', 403);
        }

        $courses = Course::query()
            ->select(['id', 'slug', 'title'])
            ->where(function ($query) use ($user) {
                $query
                    ->where('instructor_id', $user->id)
                    ->orWhereHas('admins', function ($adminQuery) use ($user) {
                        $adminQuery->where('user_id', $user->id);
                    });
            })
            ->get();

        $courseIds = $courses->pluck('id')->all();
        $perPage = max(1, (int) $request->query('per_page', 15));

        // Handle course_slug filter
        $courseSlug = $request->input('filter.course_slug');
        if ($courseSlug) {
            $course = $courses->firstWhere('slug', $courseSlug);
            if (! $course) {
                return $this->error(
                    'Course tidak ditemukan atau tidak berada di bawah pengelolaan Anda.',
                    404,
                );
            }
            $paginator = $this->service->paginateByCourse($course->id, $perPage);
        } else {
            $paginator = $this->service->paginateByCourseIds($courseIds, $perPage);
        }

        return $this->paginateResponse($paginator, 'Daftar enrollment berhasil diambil.');
    }

    /**
     * @summary Daftar ke Kursus
     *
     * @description Mendaftarkan user ke kursus. Jika kursus memerlukan enrollment key, key harus disertakan. Status enrollment bisa langsung active atau pending tergantung konfigurasi kursus.
     *
     * Requires: Student
     *
     * @response 200 {"success": true, "data": {"enrollment": {"id": 1, "user_id": 1, "course_id": 1, "status": "active", "enrolled_at": "2024-01-15T10:00:00Z"}}, "message": "Berhasil mendaftar ke kursus."}
     * @response 403 {"success": false, "message": "Hanya peserta yang dapat melakukan enrollment."}
     * @response 422 {"success": false, "message": "Enrollment key tidak valid."}
     */
    public function enroll(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if (! $user->hasRole('Student')) {
            return $this->error('Hanya peserta yang dapat melakukan enrollment.', 403);
        }

        $request->validate([
            'enrollment_key' => ['nullable', 'string', 'max:100'],
        ]);

        $dto = CreateEnrollmentDTO::fromRequest([
            'course_id' => $course->id,
            'enrollment_key' => $request->input('enrollment_key'),
        ]);

        $result = $this->service->enroll($course, $user, $dto);

        return $this->success(
            ['enrollment' => $result['enrollment']],
            $result['message'],
        );
    }

    /**
     * @summary Batalkan Permintaan Pendaftaran
     *
     * @description Membatalkan permintaan enrollment yang masih pending. Superadmin dapat membatalkan enrollment user lain dengan menyertakan user_id.
     *
     * Requires: Student (own), Superadmin (any)
     *
     * @response 200 {"success": true, "data": {"enrollment": {"id": 1, "status": "cancelled"}}, "message": "Permintaan enrollment berhasil dibatalkan."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk membatalkan enrollment ini."}
     * @response 404 {"success": false, "message": "Permintaan enrollment tidak ditemukan untuk course ini."}
     */
    public function cancel(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $targetUserId = (int) $user->id;
        if ($user->hasRole('Superadmin')) {
            $targetUserId = (int) $request->input('user_id', $user->id);
        }

        $enrollment = $this->service->findByCourseAndUser($course->id, $targetUserId);

        if (! $enrollment) {
            return $this->error('Permintaan enrollment tidak ditemukan untuk course ini.', 404);
        }

        if (! $this->canModifyEnrollment($user, $enrollment)) {
            return $this->error('Anda tidak memiliki akses untuk membatalkan enrollment ini.', 403);
        }

        $updated = $this->service->cancel($enrollment);

        return $this->success(['enrollment' => $updated], 'Permintaan enrollment berhasil dibatalkan.');
    }

    /**
     * @summary Undur Diri dari Kursus
     *
     * @description Mengundurkan diri dari kursus yang sudah aktif. Progress pembelajaran akan disimpan jika user mendaftar kembali. Superadmin dapat mengundurkan user lain.
     *
     * Requires: Student (own), Superadmin (any)
     *
     * @response 200 {"success": true, "data": {"enrollment": {"id": 1, "status": "withdrawn"}}, "message": "Anda berhasil mengundurkan diri dari course."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk mengundurkan diri dari enrollment ini."}
     * @response 404 {"success": false, "message": "Enrollment tidak ditemukan untuk course ini."}
     */
    public function withdraw(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $targetUserId = (int) $user->id;
        if ($user->hasRole('Superadmin')) {
            $targetUserId = (int) $request->input('user_id', $user->id);
        }

        $enrollment = $this->service->findByCourseAndUser($course->id, $targetUserId);

        if (! $enrollment) {
            return $this->error('Enrollment tidak ditemukan untuk course ini.', 404);
        }

        if (! $this->canModifyEnrollment($user, $enrollment)) {
            return $this->error(
                'Anda tidak memiliki akses untuk mengundurkan diri dari enrollment ini.',
                403,
            );
        }

        $updated = $this->service->withdraw($enrollment);

        return $this->success(
            ['enrollment' => $updated],
            'Anda berhasil mengundurkan diri dari course.',
        );
    }

    /**
     * @summary Status Pendaftaran
     *
     * @description Mengecek status enrollment user pada kursus tertentu. Mengembalikan status "not_enrolled" jika belum terdaftar.
     *
     * Requires: Student (own), Admin, Instructor (course owner), Superadmin
     *
     * @queryParam user_id integer ID user untuk dicek (Superadmin only). Example: 1
     *
     * @response 200 {"success": true, "data": {"status": "active", "enrollment": {"id": 1, "user_id": 1, "course_id": 1, "status": "active", "course": {"id": 1, "title": "Belajar Laravel"}}}, "message": "Status enrollment berhasil diambil."}
     * @response 200 {"success": true, "data": {"status": "not_enrolled", "enrollment": null}, "message": "Anda belum terdaftar pada course ini."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk melihat status enrollment ini."}
     */
    public function status(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $targetUserId = (int) $user->id;
        if ($user->hasRole('Superadmin')) {
            $targetUserId = (int) $request->query('user_id', $user->id);
        }

        $enrollment = $this->service->findByCourseAndUser($course->id, $targetUserId);

        if (! $enrollment) {
            return $this->success(
                [
                    'status' => 'not_enrolled',
                    'enrollment' => null,
                ],
                'Anda belum terdaftar pada course ini.',
            );
        }

        if (
            ! $this->canModifyEnrollment($user, $enrollment) &&
            ! $this->userCanManageCourse($user, $course)
        ) {
            return $this->error('Anda tidak memiliki akses untuk melihat status enrollment ini.', 403);
        }

        $enrollmentData = $enrollment->fresh(['course:id,title,slug', 'user:id,name,email']);

        return $this->success(
            [
                'status' => $enrollmentData->status,
                'enrollment' => $enrollmentData,
            ],
            'Status enrollment berhasil diambil.',
        );
    }

    /**
     * @summary Setujui Pendaftaran
     *
     * @description Menyetujui permintaan enrollment yang masih pending. Hanya instructor atau admin kursus yang dapat menyetujui.
     *
     * Requires: Admin, Instructor (course owner), Superadmin
     *
     * @response 200 {"success": true, "data": {"enrollment": {"id": 1, "status": "active", "approved_at": "2024-01-15T10:00:00Z"}}, "message": "Permintaan enrollment disetujui."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk menyetujui enrollment ini."}
     */
    public function approve(Enrollment $enrollment)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $enrollment->loadMissing('course');

        if (! $enrollment->course || ! $this->userCanManageCourse($user, $enrollment->course)) {
            return $this->error('Anda tidak memiliki akses untuk menyetujui enrollment ini.', 403);
        }

        $updated = $this->service->approve($enrollment);

        return $this->success(['enrollment' => $updated], 'Permintaan enrollment disetujui.');
    }

    /**
     * @summary Tolak Pendaftaran
     *
     * @description Menolak permintaan enrollment yang masih pending. Hanya instructor atau admin kursus yang dapat menolak.
     *
     * Requires: Admin, Instructor (course owner), Superadmin
     *
     * @response 200 {"success": true, "data": {"enrollment": {"id": 1, "status": "declined"}}, "message": "Permintaan enrollment ditolak."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk menolak enrollment ini."}
     */
    public function decline(Enrollment $enrollment)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $enrollment->loadMissing('course');

        if (! $enrollment->course || ! $this->userCanManageCourse($user, $enrollment->course)) {
            return $this->error('Anda tidak memiliki akses untuk menolak enrollment ini.', 403);
        }

        $updated = $this->service->decline($enrollment);

        return $this->success(['enrollment' => $updated], 'Permintaan enrollment ditolak.');
    }

    /**
     * @summary Hapus Pendaftaran dari Kursus
     *
     * @description Mengeluarkan peserta dari kursus. Hanya instructor atau admin kursus yang dapat mengeluarkan peserta.
     *
     * Requires: Admin, Instructor (course owner), Superadmin
     *
     * @response 200 {"success": true, "data": {"enrollment": {"id": 1, "status": "removed"}}, "message": "Peserta berhasil dikeluarkan dari course."}
     * @response 403 {"success": false, "message": "Anda tidak memiliki akses untuk mengeluarkan peserta dari course ini."}
     */
    public function remove(Enrollment $enrollment)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $enrollment->loadMissing('course');

        if (! $enrollment->course || ! $this->userCanManageCourse($user, $enrollment->course)) {
            return $this->error(
                'Anda tidak memiliki akses untuk mengeluarkan peserta dari course ini.',
                403,
            );
        }

        $updated = $this->service->remove($enrollment);

        return $this->success(['enrollment' => $updated], 'Peserta berhasil dikeluarkan dari course.');
    }

    private function canModifyEnrollment($user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return (int) $enrollment->user_id === (int) $user->id;
    }

    private function userCanManageCourse($user, Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin') || $user->hasRole('Instructor')) {
            if ((int) $course->instructor_id === (int) $user->id) {
                return true;
            }

            if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
                return true;
            }
        }

        return false;
    }
}
