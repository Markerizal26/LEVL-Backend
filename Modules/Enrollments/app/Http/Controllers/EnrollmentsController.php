<?php

namespace Modules\Enrollments\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Services\EnrollmentService;
use Modules\Schemes\Models\Course;

class EnrollmentsController extends Controller
{
  use ApiResponse;

  public function __construct(private EnrollmentService $service) {}

  /**
   * Super-admin can list all enrollments (optional filters).
   */
  public function index(Request $request)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    if (!$user->hasRole("Superadmin")) {
      return $this->error("Anda tidak memiliki akses untuk melihat seluruh enrollment.", 403);
    }

    $query = Enrollment::query()
      ->with(["user:id,name,email", "course:id,slug,title,enrollment_type"])
      ->orderByDesc("created_at");

    if ($status = $request->query("status")) {
      $query->where("status", $status);
    }

    if ($courseId = $request->query("course_id")) {
      $query->where("course_id", $courseId);
    }

    if ($userId = $request->query("user_id")) {
      $query->where("user_id", $userId);
    }

    $perPage = (int) $request->query("per_page", 15);
    $paginator = $query->paginate(max(1, $perPage))->appends($request->query());

    return $this->paginateResponse($paginator, "Daftar enrollment berhasil diambil.");
  }

  /**
   * Course admin/instructor/superadmin can list enrollments for a course.
   */
  public function indexByCourse(Request $request, Course $course)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    if (!$this->userCanManageCourse($user, $course)) {
      return $this->error("Anda tidak memiliki akses untuk melihat enrollment course ini.", 403);
    }

    $query = Enrollment::query()
      ->where("course_id", $course->id)
      ->with(["user:id,name,email"])
      ->orderByDesc("created_at");

    if ($status = $request->query("status")) {
      $query->where("status", $status);
    }

    $perPage = (int) $request->query("per_page", 15);
    $paginator = $query->paginate(max(1, $perPage))->appends($request->query());

    return $this->paginateResponse($paginator, "Daftar enrollment course berhasil diambil.");
  }

  /**
   * Admin/instructor view of all enrollments across their managed courses.
   */
  public function indexManaged(Request $request)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    if ($user->hasRole("Superadmin")) {
      return $this->index($request);
    }

    if (!$user->hasRole("Admin") && !$user->hasRole("Instructor")) {
      return $this->error("Anda tidak memiliki akses untuk melihat enrollment ini.", 403);
    }

    $courses = Course::query()
      ->select(["id", "slug", "title"])
      ->where(function ($query) use ($user) {
        $query
          ->where("instructor_id", $user->id)
          ->orWhereHas("admins", function ($adminQuery) use ($user) {
            $adminQuery->where("user_id", $user->id);
          });
      })
      ->get();

    $courseIds = $courses->pluck("id")->all();

    $query = Enrollment::query()
      ->with(["user:id,name,email", "course:id,slug,title,enrollment_type"])
      ->orderByDesc("created_at");

    if (!empty($courseIds)) {
      $query->whereIn("course_id", $courseIds);
    } else {
      $query->whereRaw("1 = 0");
    }

    if ($status = $request->query("status")) {
      $query->where("status", $status);
    }

    if ($courseSlug = $request->query("course_slug")) {
      $course = $courses->firstWhere("slug", $courseSlug);
      if (!$course) {
        return $this->error(
          "Course tidak ditemukan atau tidak berada di bawah pengelolaan Anda.",
          404,
        );
      }
      $query->where("course_id", $course->id);
    }

    $perPage = (int) $request->query("per_page", 15);
    $paginator = $query->paginate(max(1, $perPage))->appends($request->query());

    return $this->paginateResponse($paginator, "Daftar enrollment berhasil diambil.");
  }

  /**
   * Student enrols to a course.
   */
  public function enroll(Request $request, Course $course)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    if (!$user->hasRole("Student")) {
      return $this->error("Hanya peserta yang dapat melakukan enrollment.", 403);
    }

    $request->validate([
      "enrollment_key" => ["nullable", "string", "max:100"],
    ]);

    try {
      $result = $this->service->enroll($course, $user, $request->only("enrollment_key"));
    } catch (ValidationException $e) {
      throw $e;
    }

    return $this->success(
      [
        "enrollment" => $result["enrollment"],
      ],
      $result["message"],
    );
  }

  /**
   * Student cancels a pending enrollment request.
   */
  public function cancel(Request $request, Course $course)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $targetUserId = (int) $user->id;
    if ($user->hasRole("Superadmin")) {
      $targetUserId = (int) $request->input("user_id", $user->id);
    }

    $enrollment = Enrollment::query()
      ->where("course_id", $course->id)
      ->when(
        $user->hasRole("Superadmin"),
        fn($query) => $query->where("user_id", $targetUserId),
        fn($query) => $query->where("user_id", $user->id),
      )
      ->first();

    if (!$enrollment) {
      return $this->error("Permintaan enrollment tidak ditemukan untuk course ini.", 404);
    }

    if (!$this->canModifyEnrollment($user, $enrollment)) {
      return $this->error("Anda tidak memiliki akses untuk membatalkan enrollment ini.", 403);
    }

    $updated = $this->service->cancel($enrollment);

    return $this->success(["enrollment" => $updated], "Permintaan enrollment berhasil dibatalkan.");
  }

  /**
   * Student withdraws from an active course.
   */
  public function withdraw(Request $request, Course $course)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $targetUserId = (int) $user->id;

    if ($user->hasRole("Superadmin")) {
      $targetUserId = (int) $request->input("user_id", $user->id);
    }

    $enrollment = Enrollment::query()
      ->where("course_id", $course->id)
      ->when(
        !$user->hasRole("Superadmin"),
        function ($query) use ($user) {
          $query->where("user_id", $user->id);
        },
        function ($query) use ($targetUserId) {
          $query->where("user_id", $targetUserId);
        },
      )
      ->first();

    if (!$enrollment) {
      return $this->error("enrollment tidak ditemukan untuk course ini.", 404);
    }

    if (!$this->canModifyEnrollment($user, $enrollment)) {
      return $this->error(
        "Anda tidak memiliki akses untuk mengundurkan diri dari enrollment ini.",
        403,
      );
    }

    $updated = $this->service->withdraw($enrollment);

    return $this->success(
      ["enrollment" => $updated],
      "Anda berhasil mengundurkan diri dari course.",
    );
  }

  /**
   * Get enrollment status for the authenticated student (or specified user_id for superadmin).
   */
  public function status(Request $request, Course $course)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $targetUserId = (int) $user->id;

    if ($user->hasRole("Superadmin")) {
      $targetUserId = (int) $request->query("user_id", $user->id);
    }

    $enrollment = Enrollment::query()
      ->where("course_id", $course->id)
      ->where("user_id", $targetUserId)
      ->first();

    if (!$enrollment) {
      return $this->success(
        [
          "status" => "not_enrolled",
          "enrollment" => null,
        ],
        "Anda belum terdaftar pada course ini.",
      );
    }

    if (
      !$this->canModifyEnrollment($user, $enrollment) &&
      !$this->userCanManageCourse($user, $course)
    ) {
      return $this->error("Anda tidak memiliki akses untuk melihat status enrollment ini.", 403);
    }

    $enrollmentData = $enrollment->fresh(["course:id,title,slug", "user:id,name,email"]);

    return $this->success(
      [
        "status" => $enrollmentData->status,
        "enrollment" => $enrollmentData,
      ],
      "Status enrollment berhasil diambil.",
    );
  }

  /**
   * Approve a pending enrollment request.
   */
  public function approve(Enrollment $enrollment)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $enrollment->loadMissing("course");

    if (!$enrollment->course || !$this->userCanManageCourse($user, $enrollment->course)) {
      return $this->error("Anda tidak memiliki akses untuk menyetujui enrollment ini.", 403);
    }

    $updated = $this->service->approve($enrollment);

    return $this->success(["enrollment" => $updated], "Permintaan enrollment disetujui.");
  }

  /**
   * Decline a pending enrollment request.
   */
  public function decline(Request $request, Enrollment $enrollment)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $enrollment->loadMissing("course");

    if (!$enrollment->course || !$this->userCanManageCourse($user, $enrollment->course)) {
      return $this->error("Anda tidak memiliki akses untuk menolak enrollment ini.", 403);
    }

    $updated = $this->service->decline($enrollment);

    return $this->success(["enrollment" => $updated], "Permintaan enrollment ditolak.");
  }

  /**
   * Remove an enrollment from a course.
   */
  public function remove(Enrollment $enrollment)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();

    $enrollment->loadMissing("course");

    if (!$enrollment->course || !$this->userCanManageCourse($user, $enrollment->course)) {
      return $this->error(
        "Anda tidak memiliki akses untuk mengeluarkan peserta dari course ini.",
        403,
      );
    }

    $updated = $this->service->remove($enrollment);

    return $this->success(["enrollment" => $updated], "Peserta berhasil dikeluarkan dari course.");
  }

  private function canModifyEnrollment($user, Enrollment $enrollment): bool
  {
    if ($user->hasRole("Superadmin")) {
      return true;
    }

    return (int) $enrollment->user_id === (int) $user->id;
  }

  private function userCanManageCourse($user, Course $course): bool
  {
    if ($user->hasRole("Superadmin")) {
      return true;
    }

    if ($user->hasRole("Admin") || $user->hasRole("Instructor")) {
      if ((int) $course->instructor_id === (int) $user->id) {
        return true;
      }

      if (method_exists($course, "hasAdmin") && $course->hasAdmin($user)) {
        return true;
      }
    }

    return false;
  }
}
