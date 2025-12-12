<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\ProgressionService;

/**
 * @tags Progress Belajar
 */
class ProgressController extends Controller
{
  use ApiResponse;

  public function __construct(private ProgressionService $progression) {}

  /**
   * Lihat Progress Belajar
   *
   *
   * @summary Lihat Progress Belajar
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Progress"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Progress tidak ditemukan."}
   * @authenticated
   */
  public function show(Request $request, Course $course)
  {
    /** @var \Modules\Auth\Models\User|null $user */
    $user = auth("api")->user();
    if (!$user) {
      return $this->error(__("messages.progress.not_authenticated"), 401);
    }

    $targetUserId = (int) ($request->query("user_id") ?? $user->id);

    $isStudent = $user->hasRole("Student");
    $isAdmin = $user->hasRole("Admin") || $user->hasRole("Superadmin");
    $isInstructor = $user->hasRole("Instructor");

    $authorized = false;

    if ($isStudent) {
      if ($targetUserId !== (int) $user->id) {
        return $this->error(__("messages.progress.no_view_other_access"), 403);
      }
      $authorized = true;
    } elseif ($isAdmin) {
      $authorized = true;
      if (!$request->has("user_id")) {
        return $this->validationError([
          "user_id" => ["Parameter user_id wajib diisi untuk melihat progress peserta lain."],
        ]);
      }
    } elseif ($isInstructor) {
      $managesCourse = $course->hasInstructor($user) || $course->hasAdmin($user);
      if (!$managesCourse) {
        return $this->error(__("messages.progress.no_course_access"), 403);
      }

      if (!$request->has("user_id")) {
        return $this->validationError([
          "user_id" => ["Parameter user_id wajib diisi untuk melihat progress peserta lain."],
        ]);
      }

      $authorized = true;
    }

    if (!$authorized) {
      return $this->error(__("messages.progress.role_forbidden"), 403);
    }

    $enrollment = $this->progression->getEnrollmentForCourse($course->id, $targetUserId);
    if (!$enrollment) {
      return $this->error(__("messages.progress.enrollment_not_found"), 404);
    }

    $data = $this->progression->getCourseProgressData($course, $enrollment);

    return $this->success($data);
  }

  /**
   * Tandai Lesson Selesai
   *
   *
   * @summary Tandai Lesson Selesai
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Progress"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function completeLesson(Request $request, Course $course, Unit $unit, Lesson $lesson)
  {
    /** @var \Modules\Auth\Models\User|null $user */
    $user = auth("api")->user();
    if (!$user) {
      return $this->error(__("messages.progress.not_authenticated"), 401);
    }

    if (!$user->hasRole("Student")) {
      return $this->error(__("messages.progress.student_only"), 403);
    }

    if ((int) $unit->course_id !== (int) $course->id) {
      return $this->error(__("messages.progress.unit_not_in_course"), 404);
    }

    if ((int) $lesson->unit_id !== (int) $unit->id) {
      return $this->error(__("messages.progress.lesson_not_in_unit"), 404);
    }

    if ($lesson->status !== "published") {
      return $this->error(__("messages.progress.lesson_unavailable"), 403);
    }

    $enrollment = $this->progression->getEnrollmentForCourse($course->id, $user->id);
    if (!$enrollment) {
      return $this->error(__("messages.progress.not_enrolled"), 403);
    }

    if (!$this->progression->canAccessLesson($lesson, $enrollment)) {
      return $this->error(__("messages.progress.locked_prerequisite"), 403);
    }

    $this->progression->markLessonCompleted($lesson, $enrollment);

    $data = $this->progression->getCourseProgressData($course, $enrollment);

    return $this->success($data, __("messages.progress.updated"));
  }
}
