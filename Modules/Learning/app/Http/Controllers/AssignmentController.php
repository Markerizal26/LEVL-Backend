<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Learning\Contracts\Services\AssignmentServiceInterface;
use Modules\Learning\Contracts\Services\QuestionServiceInterface;
use Modules\Learning\Http\Requests\DuplicateAssignmentRequest;
use Modules\Learning\Http\Requests\GrantOverrideRequest;
use Modules\Learning\Http\Requests\StoreAssignmentRequest;
use Modules\Learning\Http\Requests\StoreQuestionRequest;
use Modules\Learning\Http\Requests\UpdateAssignmentRequest;
use Modules\Learning\Http\Requests\UpdateQuestionRequest;
use Modules\Learning\Http\Resources\AssignmentResource;
use Modules\Learning\Http\Resources\OverrideResource;
use Modules\Learning\Http\Resources\QuestionResource;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Question;

class AssignmentController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly AssignmentServiceInterface $assignmentService,
        private readonly QuestionServiceInterface $questionService
    ) {}

    public function index(
        Request $request,
        \Modules\Schemes\Models\Course $course,
        \Modules\Schemes\Models\Unit $unit,
        \Modules\Schemes\Models\Lesson $lesson,
    ): JsonResponse {
        $assignments = $this->assignmentService->listByLesson($lesson, $request->all());

        return $this->success(['assignments' => AssignmentResource::collection($assignments)]);
    }

    public function store(
        StoreAssignmentRequest $request,
        \Modules\Schemes\Models\Course $course,
        \Modules\Schemes\Models\Unit $unit,
        \Modules\Schemes\Models\Lesson $lesson,
    ): JsonResponse {
        $this->authorize('create', Assignment::class);

        $user = auth('api')->user();

        $validated = $request->validated();
        $validated['lesson_id'] = $lesson->id;

        $assignment = $this->assignmentService->create($validated, $user->id);

        return $this->created(
            ['assignment' => AssignmentResource::make($assignment)],
            __('messages.assignments.created')
        );
    }

    public function show(Assignment $assignment): JsonResponse
    {
        $assignment = $this->assignmentService->getWithRelations($assignment);

        return $this->success(['assignment' => AssignmentResource::make($assignment)]);
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        $validated = $request->validated();

        $updated = $this->assignmentService->update($assignment, $validated);

        return $this->success(
            ['assignment' => AssignmentResource::make($updated)],
            __('messages.assignments.updated')
        );
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        $this->authorize('delete', $assignment);

        $this->assignmentService->delete($assignment);

        return $this->success([], __('messages.assignments.deleted'));
    }

    public function publish(Assignment $assignment): JsonResponse
    {
        $this->authorize('publish', $assignment);

        $updated = $this->assignmentService->publish($assignment);

        return $this->success(
            ['assignment' => AssignmentResource::make($updated)],
            __('messages.assignments.published')
        );
    }

    public function unpublish(Assignment $assignment): JsonResponse
    {
        $this->authorize('publish', $assignment);

        $updated = $this->assignmentService->unpublish($assignment);

        return $this->success(
            ['assignment' => AssignmentResource::make($updated)],
            __('messages.assignments.unpublished')
        );
    }

    public function listQuestions(Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $questions = $this->questionService->getQuestionsByAssignment($assignment->id);

        return $this->success(['questions' => QuestionResource::collection($questions)]);
    }

    public function addQuestion(StoreQuestionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        $validated = $request->validated();

        $question = $this->questionService->createQuestion($assignment->id, $validated);

        return $this->created(
            ['question' => QuestionResource::make($question)],
            __('messages.questions.created')
        );
    }

    public function updateQuestion(
        UpdateQuestionRequest $request,
        Assignment $assignment,
        Question $question
    ): JsonResponse {
        $this->authorize('update', $assignment);

        $validated = $request->validated();

        $updated = $this->questionService->updateQuestion($question->id, $validated, $assignment->id);

        return $this->success(
            ['question' => QuestionResource::make($updated)],
            __('messages.questions.updated')
        );
    }

    public function deleteQuestion(Assignment $assignment, Question $question): JsonResponse
    {
        $this->authorize('update', $assignment);

        $this->questionService->deleteQuestion($question->id, $assignment->id);

        return $this->success([], __('messages.questions.deleted'));
    }

    public function checkPrerequisites(Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->assignmentService->checkPrerequisites($assignment->id, $user->id);

        return $this->success($result->toArray());
    }

    public function grantOverride(GrantOverrideRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('grantOverride', $assignment);

        $validated = $request->validated();

        $user = auth('api')->user();

        $override = $this->assignmentService->grantOverride(
            assignmentId: $assignment->id,
            studentId: (int) $validated['student_id'],
            overrideType: (string) $validated['type'],
            reason: (string) $validated['reason'],
            value: $validated['value'] ?? [],
            grantorId: $user->id
        );

        return $this->created(
            ['override' => OverrideResource::make($override)],
            __('messages.overrides.granted')
        );
    }

    public function listOverrides(Assignment $assignment): JsonResponse
    {
        $this->authorize('viewOverrides', $assignment);

        $overrides = $this->assignmentService->getOverridesForAssignment($assignment->id);

        return $this->success(['overrides' => OverrideResource::collection($overrides)]);
    }

    public function duplicate(DuplicateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('duplicate', $assignment);

        $user = auth('api')->user();

        $overrides = $request->validated();
        $overrides['created_by'] = $user->id;

        $duplicated = $this->assignmentService->duplicateAssignment($assignment->id, $overrides);

        return $this->created(
            ['assignment' => AssignmentResource::make($duplicated)],
            __('messages.assignments.duplicated')
        );
    }
}
