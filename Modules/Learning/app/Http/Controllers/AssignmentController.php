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

/**
 * Controller for managing assignments and their related resources.
 *
 * Handles:
 * - Assignment CRUD operations (Requirement 1.1)
 * - Question management (Requirements 3.1-3.8)
 * - Prerequisite checking (Requirement 2.1)
 * - Override granting (Requirements 24.1-24.4)
 * - Assignment duplication (Requirement 25.1)
 */
class AssignmentController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly AssignmentServiceInterface $assignmentService,
        private readonly QuestionServiceInterface $questionService
    ) {}

    // =========================================================================
    // Assignment CRUD Operations (Requirement 1.1)
    // =========================================================================

    /**
     * List assignments for a lesson.
     *
     * GET /courses/{course}/units/{unit}/lessons/{lesson}/assignments
     */
    public function index(
        Request $request,
        \Modules\Schemes\Models\Course $course,
        \Modules\Schemes\Models\Unit $unit,
        \Modules\Schemes\Models\Lesson $lesson,
    ): JsonResponse {
        $assignments = $this->assignmentService->listByLesson($lesson, $request->all());

        return $this->success(['assignments' => AssignmentResource::collection($assignments)]);
    }

    /**
     * Create a new assignment.
     *
     * POST /courses/{course}/units/{unit}/lessons/{lesson}/assignments
     */
    public function store(
        StoreAssignmentRequest $request,
        \Modules\Schemes\Models\Course $course,
        \Modules\Schemes\Models\Unit $unit,
        \Modules\Schemes\Models\Lesson $lesson,
    ): JsonResponse {
        $this->authorize('create', Assignment::class);

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $validated = $request->validated();
        $validated['lesson_id'] = $lesson->id;

        $assignment = $this->assignmentService->create($validated, $user->id);

        return $this->created(
            ['assignment' => AssignmentResource::make($assignment)],
            __('messages.assignments.created')
        );
    }

    /**
     * Show a single assignment.
     *
     * GET /assignments/{assignment}
     */
    public function show(Assignment $assignment): JsonResponse
    {
        $assignment = $this->assignmentService->getWithRelations($assignment);

        return $this->success(['assignment' => AssignmentResource::make($assignment)]);
    }

    /**
     * Update an assignment.
     *
     * PUT /assignments/{assignment}
     */
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

    /**
     * Delete an assignment.
     *
     * DELETE /assignments/{assignment}
     */
    public function destroy(Assignment $assignment): JsonResponse
    {
        $this->authorize('delete', $assignment);

        $this->assignmentService->delete($assignment);

        return $this->success([], __('messages.assignments.deleted'));
    }

    /**
     * Publish an assignment.
     *
     * PUT /assignments/{assignment}/publish
     */
    public function publish(Assignment $assignment): JsonResponse
    {
        $this->authorize('publish', $assignment);

        $updated = $this->assignmentService->publish($assignment);

        return $this->success(
            ['assignment' => AssignmentResource::make($updated)],
            __('messages.assignments.published')
        );
    }

    /**
     * Unpublish an assignment.
     *
     * PUT /assignments/{assignment}/unpublish
     */
    public function unpublish(Assignment $assignment): JsonResponse
    {
        $this->authorize('publish', $assignment);

        $updated = $this->assignmentService->unpublish($assignment);

        return $this->success(
            ['assignment' => AssignmentResource::make($updated)],
            __('messages.assignments.unpublished')
        );
    }

    // =========================================================================
    // Question Management (Requirements 3.1-3.8)
    // =========================================================================

    /**
     * List questions for an assignment.
     *
     * GET /assignments/{assignment}/questions
     */
    public function listQuestions(Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $questions = $this->questionService->getQuestionsByAssignment($assignment->id);

        return $this->success(['questions' => QuestionResource::collection($questions)]);
    }

    /**
     * Add a question to an assignment.
     *
     * POST /assignments/{assignment}/questions
     */
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

    /**
     * Update a question.
     *
     * PUT /assignments/{assignment}/questions/{question}
     */
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

    /**
     * Delete a question from an assignment.
     *
     * DELETE /assignments/{assignment}/questions/{question}
     */
    public function deleteQuestion(Assignment $assignment, Question $question): JsonResponse
    {
        $this->authorize('update', $assignment);

        $this->questionService->deleteQuestion($question->id, $assignment->id);

        return $this->success([], __('messages.questions.deleted'));
    }

    // =========================================================================
    // Prerequisite Checking (Requirement 2.1)
    // =========================================================================

    /**
     * Check if the current student can access an assignment based on prerequisites.
     *
     * GET /assignments/{assignment}/check-prerequisites
     *
     * Returns:
     * - can_access: boolean indicating if the student can access the assignment
     * - incomplete_prerequisites: array of incomplete prerequisite assignments (if any)
     *
     * Requirements: 2.1, 2.2, 2.3, 2.4, 2.6
     */
    public function checkPrerequisites(Assignment $assignment): JsonResponse
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $result = $this->assignmentService->checkPrerequisites($assignment->id, $user->id);

        return $this->success($result->toArray());
    }

    // =========================================================================
    // Override Granting (Requirements 24.1-24.4)
    // =========================================================================

    /**
     * Grant an override to a student for an assignment.
     *
     * POST /assignments/{assignment}/overrides
     *
     * Override types:
     * - prerequisite: Bypass prerequisite requirements (24.1)
     * - attempts: Grant additional attempts (24.2)
     * - deadline: Extend the deadline (24.3)
     *
     * All overrides require a reason (24.4).
     */
    public function grantOverride(GrantOverrideRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('grantOverride', $assignment);

        /** @var array{student_id: int, type: string, reason: string, value?: array<string, mixed>} $validated */
        $validated = $request->validated();

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $override = $this->assignmentService->grantOverride(
            assignmentId: $assignment->id,
            studentId: (int) $validated['student_id'],
            overrideType: (string) $validated['type'],
            reason: (string) $validated['reason'],
            value: $validated['value'] ?? [],
            grantorId: $user->id
        );

        $override->load(['student', 'grantor']);

        return $this->created(
            ['override' => OverrideResource::make($override)],
            __('messages.overrides.granted')
        );
    }

    /**
     * List all overrides for an assignment.
     *
     * GET /assignments/{assignment}/overrides
     */
    public function listOverrides(Assignment $assignment): JsonResponse
    {
        $this->authorize('viewOverrides', $assignment);

        $overrides = $this->assignmentService->getOverridesForAssignment($assignment->id);

        return $this->success(['overrides' => OverrideResource::collection($overrides)]);
    }

    // =========================================================================
    // Assignment Duplication (Requirement 25.1)
    // =========================================================================

    /**
     * Duplicate an assignment with all questions and settings.
     *
     * POST /assignments/{assignment}/duplicate
     *
     * Creates a copy of the assignment including:
     * - All questions with their settings (25.1)
     * - All configuration options
     * - Prerequisite relationships
     *
     * Does NOT copy:
     * - Submissions or grades (25.2)
     *
     * The duplicated assignment:
     * - Gets a new unique ID (25.4)
     * - Defaults to draft status
     * - Can have overridden values via request body (25.3)
     */
    public function duplicate(DuplicateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('duplicate', $assignment);

        /** @var \Modules\Auth\Models\User $user */
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
