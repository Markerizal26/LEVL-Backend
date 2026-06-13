<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Grading\Http\Requests\BatchEssayRequest;
use Modules\Grading\Http\Requests\BulkFeedbackRequest;
use Modules\Grading\Http\Requests\BulkReleaseGradesRequest;
use Modules\Grading\Http\Requests\GradingQueueRequest;
use Modules\Grading\Http\Requests\ManualGradeRequest;
use Modules\Grading\Http\Requests\SaveDraftGradeRequest;
use Modules\Grading\Services\GradingOrchestratorService;
use Modules\Learning\Models\QuizSubmission;
use Modules\Learning\Models\Submission;

class GradingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly GradingOrchestratorService $orchestrator,
    ) {}

    public function manualGrade(ManualGradeRequest $request, Submission $submission): JsonResponse
    {
        return $this->orchestrator->manualGrade($submission, $request->validated());
    }

    public function manualGradeUnified(ManualGradeRequest $request, int $submissionId): JsonResponse
    {
        $validated = $request->validated();
        $grades = is_array($validated['grades'] ?? null) ? $validated['grades'] : [];
        $preferQuiz = collect($grades)->contains(fn ($grade) => is_array($grade) && array_key_exists('question_id', $grade));

        if ($preferQuiz) {
            $quizSubmission = QuizSubmission::find($submissionId);

            if ($quizSubmission !== null) {
                $this->authorize('view', $quizSubmission);

                return $this->orchestrator->manualGradeQuiz($quizSubmission, $validated);
            }
        }

        $submission = Submission::find($submissionId);

        if ($submission !== null) {
            $this->authorize('grade', $submission);

            return $this->orchestrator->manualGrade($submission, $validated);
        }

        $quizSubmission = QuizSubmission::find($submissionId);

        if ($quizSubmission !== null) {
            $this->authorize('view', $quizSubmission);

            return $this->orchestrator->manualGradeQuiz($quizSubmission, $validated);
        }

        return response()->json([
            'success' => false,
            'message' => __('messages.not_found'),
            'data' => null,
            'meta' => null,
            'errors' => null,
        ], 404);
    }

    public function queue(GradingQueueRequest $request): JsonResponse
    {
        return $this->orchestrator->queue($request->all());
    }

    public function show(int $submissionId): JsonResponse
    {
        $questionId = request()->query('question_id');

        return $this->orchestrator->showSubmission(
            $submissionId,
            (string) request()->get('include', ''),
            request()->query('type'),
            is_numeric($questionId) ? (int) $questionId : null
        );
    }

    public function showQuizEssayQuestion(QuizSubmission $quizSubmission, int $questionId): JsonResponse
    {
        $this->authorize('view', $quizSubmission);

        return $this->orchestrator->showQuizEssayQuestion($quizSubmission, $questionId);
    }

    public function batchEssays(BatchEssayRequest $request): JsonResponse
    {
        return $this->orchestrator->batchEssays($request->validated()['items'], auth('api')->user());
    }

    public function returnToQueue(Submission $submission): JsonResponse
    {
        return $this->orchestrator->returnToQueue($submission);
    }

    public function saveDraftGrade(SaveDraftGradeRequest $request, Submission $submission): JsonResponse
    {
        return $this->orchestrator->saveDraftGrade($submission, $request->validated());
    }

    public function saveDraftGradeUnified(SaveDraftGradeRequest $request, int $submissionId): JsonResponse
    {
        $validated = $request->validated();
        $grades = is_array($validated['grades'] ?? null) ? $validated['grades'] : [];
        $preferQuiz = collect($grades)->contains(fn ($grade) => is_array($grade) && array_key_exists('question_id', $grade));

        if ($preferQuiz) {
            $quizSubmission = QuizSubmission::find($submissionId);

            if ($quizSubmission !== null) {
                $this->authorize('view', $quizSubmission);

                return $this->orchestrator->saveDraftGradeQuiz($quizSubmission, $validated);
            }
        }

        $submission = Submission::find($submissionId);

        if ($submission !== null) {
            $this->authorize('grade', $submission);

            return $this->orchestrator->saveDraftGrade($submission, $validated);
        }

        $quizSubmission = QuizSubmission::find($submissionId);

        if ($quizSubmission !== null) {
            $this->authorize('view', $quizSubmission);

            return $this->orchestrator->saveDraftGradeQuiz($quizSubmission, $validated);
        }

        return response()->json([
            'success' => false,
            'message' => __('messages.not_found'),
            'data' => null,
            'meta' => null,
            'errors' => null,
        ], 404);
    }

    public function getGrade(Submission $submission): JsonResponse
    {
        return $this->orchestrator->getGrade($submission);
    }

    public function releaseGradeUnified(int $submissionId): JsonResponse
    {
        $type = request()->query('type');
        $normalizedType = is_string($type) ? strtolower($type) : null;
        $preferQuiz = $normalizedType === 'quiz';

        if ($preferQuiz) {
            $quizSubmission = QuizSubmission::find($submissionId);

            if ($quizSubmission !== null) {
                $this->authorize('view', $quizSubmission);

                return $this->orchestrator->releaseGradeQuiz($quizSubmission);
            }
        }

        $submission = Submission::find($submissionId);

        if ($submission !== null) {
            $this->authorize('grade', $submission);

            return $this->orchestrator->releaseGrade($submission);
        }

        $quizSubmission = QuizSubmission::find($submissionId);

        if ($quizSubmission !== null) {
            $this->authorize('view', $quizSubmission);

            return $this->orchestrator->releaseGradeQuiz($quizSubmission);
        }

        return response()->json([
            'success' => false,
            'message' => __('messages.not_found'),
            'data' => null,
            'meta' => null,
            'errors' => null,
        ], 404);
    }

    public function bulkReleaseGrades(BulkReleaseGradesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->orchestrator->bulkReleaseGrades(
            $validated['submission_ids'] ?? [],
            $validated['targets'] ?? [],
            $request->boolean('async')
        );
    }

    public function bulkApplyFeedback(BulkFeedbackRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->orchestrator->bulkApplyFeedback(
            $validated['submission_ids'] ?? [],
            $validated['targets'] ?? [],
            $validated['feedback'],
            $request->boolean('async')
        );
    }

    public function gradingStatus(Submission $submission): JsonResponse
    {
        return $this->orchestrator->gradingStatus($submission);
    }
}
