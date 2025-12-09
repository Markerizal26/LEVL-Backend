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
     * Daftar Semua Pendaftaran (Superadmin)
     *
     * Mengambil daftar semua enrollment di sistem. Hanya Superadmin yang dapat mengakses endpoint ini.
     *
     *
     * @summary Daftar Semua Pendaftaran (Superadmin)
     * @queryParam page integer Halaman pagination. Example: 1
     * @queryParam per_page integer Items per halaman. Example: 15
     * @queryParam filter[course_id] integer Filter berdasarkan kursus. Example: 1
     * @queryParam filter[user_id] integer Filter berdasarkan user. Example: 5
     * @queryParam filter[status] string Filter berdasarkan status (pending|active|completed|cancelled). Example: active
     * @queryParam sort string Sorting field. Example: -created_at
     *
     * @allowedFilters course_id,user_id,status,enrollment_date
     *
     * @queryParam course_id string Filter berdasarkan ID kursus. Example: 
     * @queryParam user_id string Filter berdasarkan ID pengguna. Example: 
     * @queryParam status string Filter berdasarkan status. Example: 
     * @queryParam enrollment_date string Filter berdasarkan tanggal pendaftaran. Example: 
     *
     * @allowedSorts created_at,updated_at,enrollment_date,completion_date
     *
     * @queryParam sort string Field untuk sorting. Allowed: created_at, updated_at, enrollment_date, completion_date. Prefix dengan '-' untuk descending. Example: -created_at
     *
     * @response 200 scenario="Success" {"success": true, "data": {"enrollments": []}}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melihat seluruh enrollment."}
     * @response 501 scenario="Response" {"success":false,"message":"Endpoint tidak tersedia untuk saat ini."}
     *
     * @authenticated
     *
     * @role Superadmin
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
     * Daftar Pendaftaran per Kursus
     *
     * Mengambil daftar enrollment untuk kursus tertentu. Hanya instructor atau admin kursus yang dapat mengakses.
     *
     *
     * @summary Daftar Pendaftaran per Kursus
     * @queryParam page integer Halaman pagination. Example: 1
     * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
     * @queryParam filter[status] string Filter berdasarkan status. Example: active
     * @queryParam filter[user_id] integer Filter berdasarkan user. Example: 5
     * @queryParam sort string Sorting field. Example: -enrollment_date
     *
     * @allowedFilters status,user_id,enrollment_date
     *
     * @queryParam status string Filter berdasarkan status. Example: 
     * @queryParam user_id string Filter berdasarkan ID pengguna. Example: 
     * @queryParam enrollment_date string Filter berdasarkan tanggal pendaftaran. Example: 
     *
     * @allowedSorts created_at,enrollment_date,completion_date
     *
     * @queryParam sort string Field untuk sorting. Allowed: created_at, enrollment_date, completion_date. Prefix dengan '-' untuk descending. Example: -created_at
     *
     * @response 200 scenario="Success" {"success": true, "data": {"enrollments": [{"id": 1, "user_id": 1, "course_id": 1, "status": "active", "user": {"id": 1, "name": "John Doe"}}]}, "meta": {"current_page": 1, "per_page": 15, "total": 50}}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melihat enrollment course ini."}
     *
     * @authenticated
     *
     * @role Admin|Instructor|Superadmin
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
     * Daftar Pendaftaran yang Dikelola
     *
     * Mengambil daftar enrollment dari kursus yang dikelola oleh user. Admin/Instructor melihat enrollment dari kursus mereka, Superadmin melihat semua.
     *
     * Requires: Admin, Instructor, Superadmin
     *
     *
     * @summary Daftar Pendaftaran yang Dikelola
     * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
     * @queryParam filter[course_slug] string Filter berdasarkan slug kursus. Example: belajar-laravel
     *
     * @response 200 scenario="Success" {"success": true, "data": {"enrollments": [{"id": 1, "user_id": 1, "course_id": 1, "status": "active"}]}, "meta": {"current_page": 1, "per_page": 15, "total": 50}}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melihat enrollment ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Course tidak ditemukan atau tidak berada di bawah pengelolaan Anda."}
     *
     * @authenticated
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
     * Daftar ke Kursus
     *
     * Mendaftarkan user ke kursus. Jika kursus memerlukan enrollment key, key harus disertakan. Status enrollment bisa langsung active atau pending tergantung konfigurasi kursus.
     *
     * Requires: Student
     *
     *
     * @summary Daftar ke Kursus
     * @response 200 scenario="Success" {"success": true, "data": {"enrollment": {"id": 1, "user_id": 1, "course_id": 1, "status": "active", "enrolled_at": "2024-01-15T10:00:00Z"}}, "message": "Berhasil mendaftar ke kursus."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Hanya peserta yang dapat melakukan enrollment."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Enrollment key tidak valid."}
     *
     * @authenticated
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
     * Batalkan Permintaan Pendaftaran
     *
     * Membatalkan permintaan enrollment yang masih pending. Superadmin dapat membatalkan enrollment user lain dengan menyertakan user_id.
     *
     * Requires: Student (own), Superadmin (any)
     *
     *
     * @summary Batalkan Permintaan Pendaftaran
     * @response 200 scenario="Success" {"success": true, "data": {"enrollment": {"id": 1, "status": "cancelled"}}, "message": "Permintaan enrollment berhasil dibatalkan."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk membatalkan enrollment ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Permintaan enrollment tidak ditemukan untuk course ini."}
     *
     * @authenticated
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
     * Undur Diri dari Kursus
     *
     * Mengundurkan diri dari kursus yang sudah aktif. Progress pembelajaran akan disimpan jika user mendaftar kembali. Superadmin dapat mengundurkan user lain.
     *
     * Requires: Student (own), Superadmin (any)
     *
     *
     * @summary Undur Diri dari Kursus
     * @response 200 scenario="Success" {"success": true, "data": {"enrollment": {"id": 1, "status": "withdrawn"}}, "message": "Anda berhasil mengundurkan diri dari course."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mengundurkan diri dari enrollment ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Enrollment tidak ditemukan untuk course ini."}
     *
     * @authenticated
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
     * Status Pendaftaran
     *
     * Mengecek status enrollment user pada kursus tertentu. Mengembalikan status "not_enrolled" jika belum terdaftar.
     *
     * Requires: Student (own), Admin, Instructor (course owner), Superadmin
     *
     *
     * @summary Status Pendaftaran
     * @queryParam user_id integer ID user untuk dicek (Superadmin only). Example: 1
     *
     * @response 200 scenario="Success" {"success": true, "data": {"status": "active", "enrollment": {"id": 1, "user_id": 1, "course_id": 1, "status": "active", "course": {"id": 1, "title": "Belajar Laravel"}}}, "message": "Status enrollment berhasil diambil."}
     * @response 200 scenario="Success" {"success": true, "data": {"status": "not_enrolled", "enrollment": null}, "message": "Anda belum terdaftar pada course ini."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melihat status enrollment ini."}
     *
     * @authenticated
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
     * Setujui Pendaftaran
     *
     * Menyetujui permintaan enrollment yang masih pending. Hanya instructor atau admin kursus yang dapat menyetujui.
     *
     * Requires: Admin, Instructor (course owner), Superadmin
     *
     *
     * @summary Setujui Pendaftaran
     * @response 200 scenario="Success" {"success": true, "data": {"enrollment": {"id": 1, "status": "active", "approved_at": "2024-01-15T10:00:00Z"}}, "message": "Permintaan enrollment disetujui."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menyetujui enrollment ini."}
     *
     * @authenticated
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
     * Tolak Pendaftaran
     *
     * Menolak permintaan enrollment yang masih pending. Hanya instructor atau admin kursus yang dapat menolak.
     *
     * Requires: Admin, Instructor (course owner), Superadmin
     *
     *
     * @summary Tolak Pendaftaran
     * @response 200 scenario="Success" {"success": true, "data": {"enrollment": {"id": 1, "status": "declined"}}, "message": "Permintaan enrollment ditolak."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menolak enrollment ini."}
     *
     * @authenticated
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
     * Hapus Pendaftaran dari Kursus
     *
     * Mengeluarkan peserta dari kursus. Hanya instructor atau admin kursus yang dapat mengeluarkan peserta.
     *
     * Requires: Admin, Instructor (course owner), Superadmin
     *
     *
     * @summary Hapus Pendaftaran dari Kursus
     * @response 200 scenario="Success" {"success": true, "data": {"enrollment": {"id": 1, "status": "removed"}}, "message": "Peserta berhasil dikeluarkan dari course."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mengeluarkan peserta dari course ini."}
     *
     * @authenticated
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
