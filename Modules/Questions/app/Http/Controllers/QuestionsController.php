<?php

namespace Modules\Questions\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Questions\Contracts\Services\QuestionServiceInterface;
use Modules\Questions\DTOs\CreateQuestionDTO;
use Modules\Questions\DTOs\UpdateQuestionDTO;
use Modules\Questions\Http\Requests\StoreQuestionRequest;
use Modules\Questions\Http\Requests\UpdateQuestionRequest;
use Modules\Questions\Http\Resources\QuestionResource;

class QuestionsController extends Controller
{
  use ApiResponse;

  public function __construct(private readonly QuestionServiceInterface $questionService) {}

  public function index(Request $request): JsonResponse
  {
    $filters = [
      "search" => $request->input("search"),
      "type" => $request->input("type"),
      "difficulty" => $request->input("difficulty"),
      "category_id" => $request->input("category_id"),
      "status" => $request->input("status"),
      "created_by" => $request->input("created_by"),
      "sort_by" => $request->input("sort_by"),
      "sort_direction" => $request->input("sort_direction"),
    ];

    $perPage = $request->input("per_page", 15);
    $questions = $this->questionService->list($filters, $perPage);

    return $this->success(
      QuestionResource::collection($questions)->response()->getData(true),
      __("messages.questions.list_retrieved"),
    );
  }

  public function store(StoreQuestionRequest $request): JsonResponse
  {
    $dto = CreateQuestionDTO::from($request->validated());
    $question = $this->questionService->create($dto, auth()->id());

    return $this->created(new QuestionResource($question), __("messages.questions.created"));
  }

  public function show(int $id): JsonResponse
  {
    $question = $this->questionService->find($id);

    if (!$question) {
      return $this->notFound(__("messages.questions.not_found"));
    }

    return $this->success(new QuestionResource($question), __("messages.questions.retrieved"));
  }

  public function update(UpdateQuestionRequest $request, int $id): JsonResponse
  {
    $dto = UpdateQuestionDTO::from($request->validated());

    try {
      $question = $this->questionService->update($id, $dto);

      return $this->success(new QuestionResource($question), __("messages.questions.updated"));
    } catch (\Exception $e) {
      return $this->notFound($e->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      $this->questionService->delete($id);

      return $this->success(null, __("messages.questions.deleted"));
    } catch (\Exception $e) {
      return $this->notFound($e->getMessage());
    }
  }

  public function random(Request $request): JsonResponse
  {
    $filters = [
      "type" => $request->input("type"),
      "difficulty" => $request->input("difficulty"),
      "category_id" => $request->input("category_id"),
    ];

    $count = $request->input("count", 10);
    $questions = $this->questionService->getRandomQuestions($filters, $count);

    return $this->success(
      QuestionResource::collection($questions),
      __("messages.questions.random_retrieved"),
    );
  }
}
