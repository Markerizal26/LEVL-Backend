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

class CourseController extends Controller
{
  use ApiResponse;

  public function __construct(
    private CourseService $service,
    private CourseRepository $repository,
  ) {}

  public function index(Request $request)
  {
    $params = $request->all();

    $isPublicListing = ($params["status"] ?? null) === "published";

    $paginator = $isPublicListing
      ? $this->service->listPublic($params)
      : $this->service->list($params);

    return $this->paginateResponse($paginator);
  }

  public function store(CourseRequest $request)
  {
    $data = $request->validated();
    // Handle file uploads
    if ($request->hasFile("thumbnail")) {
      $data["thumbnail_path"] = upload_file($request->file("thumbnail"), "courses/thumbnails");
    }
    if ($request->hasFile("banner")) {
      $data["banner_path"] = upload_file($request->file("banner"), "courses/banners");
    }
    /** @var \Modules\Auth\Models\User|null $actor */
    $actor = auth("api")->user();

    try {
      $course = $this->service->create($data, $actor);
    } catch (UniqueConstraintViolationException | QueryException $e) {
      return $this->handleCourseUniqueConstraint($e);
    }

    return $this->created(["course" => $course], "Course berhasil dibuat.");
  }

  public function show(Course $course)
  {
    return $this->success(["course" => $course->load(["tags", "outcomes"])]);
  }

  public function update(CourseRequest $request, Course $course)
  {
    $data = $request->validated();
    if ($request->hasFile("thumbnail")) {
      $data["thumbnail_path"] = upload_file($request->file("thumbnail"), "courses/thumbnails");
    }
    if ($request->hasFile("banner")) {
      $data["banner_path"] = upload_file($request->file("banner"), "courses/banners");
    }
    try {
      $updated = $this->service->update($course->id, $data);
    } catch (UniqueConstraintViolationException | QueryException $e) {
      return $this->handleCourseUniqueConstraint($e);
    }

    return $this->success(["course" => $updated], "Course berhasil diperbarui.");
  }

  public function destroy(Course $course)
  {
    $ok = $this->service->delete($course->id);

    return $this->success([], "Course berhasil dihapus.");
  }

  public function publish(Course $course)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!\Illuminate\Support\Facades\Gate::forUser($user)->allows("update", $course)) {
      return $this->error("Anda tidak memiliki akses untuk mempublish course ini.", 403);
    }

    $updated = $this->service->publish($course->id);

    return $this->success(["course" => $updated], "Course berhasil dipublish.");
  }

  public function unpublish(Course $course)
  {
    /** @var \Modules\Auth\Models\User $user */
    $user = auth("api")->user();
    if (!\Illuminate\Support\Facades\Gate::forUser($user)->allows("update", $course)) {
      return $this->error("Anda tidak memiliki akses untuk unpublish course ini.", 403);
    }

    $updated = $this->service->unpublish($course->id);

    return $this->success(["course" => $updated], "Course berhasil diunpublish.");
  }

  /**
   * Generate a new enrollment key for the course.
   *
   * @summary Generate enrollment key
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
    $user = auth("api")->user();
    if (!\Illuminate\Support\Facades\Gate::forUser($user)->allows("update", $course)) {
      return $this->error(
        "Anda tidak memiliki akses untuk generate enrollment key course ini.",
        403,
      );
    }

    // Generate random alphanumeric key (12 characters)
    $newKey = strtoupper(\Illuminate\Support\Str::random(12));

    $updated = $this->service->update($course->id, [
      "enrollment_type" => "key_based",
      "enrollment_key" => $newKey,
    ]);

    return $this->success(
      [
        "course" => $updated->makeVisible("enrollment_key"),
        "enrollment_key" => $newKey,
      ],
      "Enrollment key berhasil digenerate.",
    );
  }

  /**
   * Update enrollment key for the course.
   *
   * @summary Update enrollment settings
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
    $user = auth("api")->user();
    if (!\Illuminate\Support\Facades\Gate::forUser($user)->allows("update", $course)) {
      return $this->error("Anda tidak memiliki akses untuk update enrollment key course ini.", 403);
    }

    $validated = $request->validate([
      "enrollment_type" => ["required", "in:auto_accept,key_based,approval"],
      "enrollment_key" => ["nullable", "string", "max:100"],
    ]);

    // If enrollment_type is key_based but no key provided, generate one
    if ($validated["enrollment_type"] === "key_based" && empty($validated["enrollment_key"])) {
      $validated["enrollment_key"] = strtoupper(\Illuminate\Support\Str::random(12));
    }

    // If enrollment_type is not key_based, clear the key
    if ($validated["enrollment_type"] !== "key_based") {
      $validated["enrollment_key"] = null;
    }

    $updated = $this->service->update($course->id, $validated);

    $response = ["course" => $updated];
    if ($validated["enrollment_type"] === "key_based") {
      $response["enrollment_key"] = $updated->enrollment_key;
      $response["course"] = $updated->makeVisible("enrollment_key");
    }

    return $this->success($response, "Enrollment settings berhasil diperbarui.");
  }

  /**
   * Remove enrollment key from the course.
   *
   * @summary Remove enrollment key
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
    $user = auth("api")->user();
    if (!\Illuminate\Support\Facades\Gate::forUser($user)->allows("update", $course)) {
      return $this->error("Anda tidak memiliki akses untuk remove enrollment key course ini.", 403);
    }

    $updated = $this->service->update($course->id, [
      "enrollment_type" => "auto_accept",
      "enrollment_key" => null,
    ]);

    return $this->success(
      ["course" => $updated],
      "Enrollment key berhasil dihapus dan enrollment type diubah ke auto_accept.",
    );
  }

  private function handleCourseUniqueConstraint(QueryException $e)
  {
    $message = $e->getMessage();

    $errors = [];
    if (str_contains($message, "courses_code_unique")) {
      $errors["code"][] = "Kode sudah digunakan.";
    }
    if (str_contains($message, "courses_slug_unique")) {
      $errors["slug"][] = "Slug sudah digunakan.";
    }

    if (!empty($errors)) {
      return $this->validationError($errors);
    }

    return $this->validationError([
      "general" => ["Data duplikat. Periksa kembali isian Anda."],
    ]);
  }
}
