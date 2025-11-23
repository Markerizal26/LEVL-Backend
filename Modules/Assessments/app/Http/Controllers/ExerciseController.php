<?php

namespace Modules\Assessments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Services\ExerciseService;

class ExerciseController extends Controller
{
  use ApiResponse;

  public function __construct(private readonly ExerciseService $service) {}

  /**
   * List all exercises with filtering
   */
  public function index(Request $request)
  {
    $perPage = max(1, (int) $request->get("per_page", 15));
    $exercises = $this->service->paginate($request->all(), $perPage);

    if ($exercises instanceof LengthAwarePaginator) {
      return $this->paginateResponse($exercises, "Daftar exercise berhasil diambil");
    }

    return $this->success(["exercises" => $exercises], "Daftar exercise berhasil diambil");
  }

  /**
   * Create new exercise
   */
  public function store(Request $request)
  {
    $user = $request->user();
    $this->authorize("create", Exercise::class);

    $validated = $request->validate([
      "scope_type" => "required|in:course,unit,lesson",
      "scope_id" => "required|integer",
      "title" => "required|string|max:255",
      "description" => "nullable|string",
      "type" => "required|in:quiz,exam,homework",
      "time_limit_minutes" => "nullable|integer|min:1",
      "max_score" => "required|numeric|min:0",
      "available_from" => "nullable|date",
      "available_until" => "nullable|date|after:available_from",
    ]);

    $exercise = $this->service->create($validated, $user->id);

    return $this->created(["exercise" => $exercise], "Exercise berhasil dibuat");
  }

  /**
   * Get exercise details
   */
  public function show(Exercise $exercise)
  {
    return $this->success(["exercise" => $exercise], "Detail exercise berhasil diambil");
  }

  /**
   * Update exercise
   */
  public function update(Request $request, Exercise $exercise)
  {
    $this->authorize("update", $exercise);

    $validated = $request->validate([
      "title" => "sometimes|string|max:255",
      "description" => "nullable|string",
      "type" => "sometimes|in:quiz,exam,homework",
      "time_limit_minutes" => "nullable|integer|min:1",
      "max_score" => "sometimes|numeric|min:0",
      "available_from" => "nullable|date",
      "available_until" => "nullable|date|after:available_from",
    ]);

    $updated = $this->service->update($exercise, $validated);

    return $this->success(["exercise" => $updated], "Exercise berhasil diperbarui");
  }

  /**
   * Delete exercise
   */
  public function destroy(Exercise $exercise)
  {
    $this->authorize("delete", $exercise);

    $this->service->delete($exercise);

    return $this->noContent();
  }

  /**
   * Publish exercise
   */
  public function publish(Exercise $exercise)
  {
    $this->authorize("update", $exercise);

    $published = $this->service->publish($exercise);

    return $this->success(["exercise" => $published], "Exercise berhasil dipublikasikan");
  }

  /**
   * Get exercise questions
   */
  public function getQuestions(Exercise $exercise)
  {
    $questions = $this->service->questions($exercise);

    return $this->success(["questions" => $questions], "Daftar pertanyaan berhasil diambil");
  }
}
