<?php

declare(strict_types=1);

namespace Modules\Grading\Services;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Modules\Auth\Models\User;
use Modules\Grading\DTOs\BulkOperationDTO;
use Modules\Grading\DTOs\SubmissionGradeDTO;
use Modules\Grading\Http\Resources\GradeResource;
use Modules\Grading\Http\Resources\GradingQueueItemResource;
use Modules\Learning\Models\QuizSubmission;
use Modules\Learning\Models\Submission;

class GradingOrchestratorService
{
    use ApiResponse;

    public function __construct(
        private readonly GradingEntryService $entryService,
        private readonly GradingQueueService $queueService,
        private readonly GradingBulkService $bulkService,
    ) {}

    public function manualGrade(Submission $submission, array $validated): JsonResponse
    {
        try {
            $hasGrades = ! empty($validated['grades']);

            if ($hasGrades) {
                $dto = new SubmissionGradeDTO(
                    submissionId: $submission->id,
                    answers: $validated['grades'],
                    scoreOverride: null,
                    feedback: $validated['feedback'] ?? null,
                    graderId: auth('api')->id()
                );

                $grade = $this->entryService->manualGrade($dto);

                return $this->success(
                    GradeResource::make($grade),
                    __('messages.grading.manual_graded')
                );
            }

            $dto = new SubmissionGradeDTO(
                submissionId: $submission->id,
                answers: [],
                scoreOverride: (float) ($validated['score'] ?? 0),
                feedback: $validated['feedback'] ?? null,
                graderId: auth('api')->id()
            );

            $grade = $this->entryService->manualGrade($dto);
            $submission->refresh();

            return $this->success(
                [
                    'submission_id' => $submission->id,
                    'score' => $submission->score,
                    'grade' => GradeResource::make($grade),
                ],
                __('messages.grading.assignment_graded')
            );
        } catch (InvalidArgumentException|\Modules\Learning\Exceptions\SubmissionException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function queue(array $filters): JsonResponse
    {
        $actor = auth('api')->user();
        $scopeToInstructorCourses = $actor
            && $actor->hasRole('Instructor')
            && ! $actor->hasAnyRole(['Admin', 'Superadmin']);

        $paginator = $this->queueService->getGradingQueue(
            $filters,
            $actor?->id,
            $scopeToInstructorCourses
        );
        $paginator->getCollection()->transform(fn ($item) => new GradingQueueItemResource($item));

        return $this->paginateResponse($paginator, 'messages.grading.queue_fetched');
    }

    public function manualGradeQuiz(QuizSubmission $quizSubmission, array $validated): JsonResponse
    {
        try {
            $grades = $validated['grades'] ?? [];

            if ($grades === []) {
                return $this->error(__('messages.validation_failed'), [], 422);
            }

            $result = $this->entryService->manualGradeQuiz($quizSubmission, $grades, auth('api')->id());

            return $this->success(
                new GradingQueueItemResource($result),
                __('messages.grading.manual_graded')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function showSubmission(int $submissionId, string $includeParam = '', ?string $type = null, ?int $questionId = null): JsonResponse
    {
        $normalizedType = is_string($type) ? strtolower($type) : null;
        $shouldResolveAssignment = $normalizedType === null || $normalizedType === 'assignment';
        $shouldResolveQuiz = $normalizedType === null || $normalizedType === 'quiz';

        $submission = $shouldResolveAssignment ? Submission::find($submissionId) : null;

        if ($submission !== null) {
            if (empty($includeParam)) {
                $submission->load([
                    'user:id,name,email',
                    'assignment:id,title,max_score,description,submission_type,unit_id,order',
                    'assignment.unit:id,course_id,order',
                    'assignment.unit.course:id,title,slug,code',
                    'answers.question',
                    'media',
                ]);
            } else {
                $submission = \Spatie\QueryBuilder\QueryBuilder::for(\Modules\Learning\Models\Submission::class)
                    ->where('id', $submission->id)
                    ->allowedIncludes(['user', 'assignment', 'assignment.unit', 'assignment.unit.course', 'answers', 'answers.question', 'grade'])
                    ->firstOrFail();
            }

            $submission->loadMissing([
                'user:id,name,email',
                'assignment:id,title,max_score,description,submission_type,unit_id,order',
                'assignment.unit:id,course_id,order',
                'assignment.unit.course:id,title,slug,code',
                'media',
                'grade',
            ]);

            return $this->success(
                new GradingQueueItemResource($submission, true),
                __('messages.grading.submission_fetched')
            );
        }

        $quizSubmission = $shouldResolveQuiz ? QuizSubmission::with([
            'user:id,name,email',
            'quiz:id,title,unit_id,order',
            'quiz.unit:id,order,course_id',
            'quiz.unit.course:id,slug,title,code',
            'answers.question',
        ])->find($submissionId) : null;

        if ($quizSubmission === null) {
            return $this->error(__('messages.not_found'), [], 404);
        }

        if ($normalizedType === 'quiz') {
            if ($questionId === null) {
                return $this->error(__('messages.validation_failed'), [], 422);
            }

            $row = $this->queueService->getQuizEssayRow($quizSubmission, $questionId);

            if ($row === null) {
                return $this->error(__('messages.not_found'), [], 404);
            }

            return $this->success(
                new GradingQueueItemResource($row, true),
                __('messages.grading.submission_fetched')
            );
        }

        return $this->success(
            new GradingQueueItemResource($quizSubmission, true),
            __('messages.grading.submission_fetched')
        );
    }

    public function showQuizEssayQuestion(QuizSubmission $quizSubmission, int $questionId): JsonResponse
    {
        $row = $this->queueService->getQuizEssayRow($quizSubmission, $questionId);

        if ($row === null) {
            return $this->error('Essay question not found for this submission.', [], 404);
        }

        return $this->success(new GradingQueueItemResource($row, true));
    }

    public function batchEssays(array $items, User $actor): JsonResponse
    {
        $results = $this->queueService->getEssaysBatch($items, $actor);

        return $this->success($results, __('messages.grading.batch_fetched'));
    }

    public function returnToQueue(Submission $submission): JsonResponse
    {
        try {
            $this->entryService->returnToQueue($submission->id);

            return $this->success(
                ['submission_id' => $submission->id, 'workflow_state' => $submission->refresh()->state?->value],
                __('messages.grading.returned_to_queue')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function saveDraftGrade(Submission $submission, array $validated): JsonResponse
    {
        try {
            $hasGrades = ! empty($validated['grades']);
            $dto = new SubmissionGradeDTO(
                submissionId: $submission->id,
                answers: $validated['grades'] ?? [],
                scoreOverride: $hasGrades ? null : (float) ($validated['score'] ?? 0),
                feedback: $validated['feedback'] ?? null,
                graderId: auth('api')->id(),
                isAiAssisted: (bool) ($validated['is_ai_assisted'] ?? false),
                aiSuggestedScore: isset($validated['ai_suggested_score']) ? (float) $validated['ai_suggested_score'] : null,
                aiReasoning: $validated['ai_reasoning'] ?? null,
                aiScoringReference: $validated['ai_scoring_reference'] ?? null
            );

            $this->entryService->saveDraftGrade($dto);

            $grade = $submission->grade()->first();

            return $this->success([
                'submission_id' => $submission->id,
                'draft_score' => $grade?->score,
                'draft_feedback' => $grade?->feedback,
                'is_draft' => true,
            ], __('messages.grading.draft_saved'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function saveDraftGradeQuiz(QuizSubmission $quizSubmission, array $validated): JsonResponse
    {
        try {
            $grades = $validated['grades'] ?? [];

            if ($grades === []) {
                return $this->error(__('messages.validation_failed'), [], 422);
            }

            $result = $this->entryService->saveDraftGradeQuiz($quizSubmission, $grades, auth('api')->id());

            return $this->success(
                new GradingQueueItemResource($result),
                __('messages.grading.draft_saved')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function getGrade(Submission $submission): JsonResponse
    {
        $grade = $this->queueService->getGrade($submission->id);

        return $this->success($grade ? GradeResource::make($grade) : null);
    }

    public function releaseGrade(Submission $submission): JsonResponse
    {
        try {
            $this->entryService->releaseGrade($submission->id);

            return $this->success(
                [
                    'submission_id' => $submission->id,
                    'workflow_state' => $submission->refresh()->state?->value,
                    'grade' => $submission->grade ? GradeResource::make($submission->grade) : null,
                ],
                __('messages.grading.grade_released')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function releaseGradeQuiz(QuizSubmission $quizSubmission): JsonResponse
    {
        try {
            $result = $this->entryService->finalizeQuizSubmission($quizSubmission);

            $result->update([
                'status' => \Modules\Learning\Enums\QuizSubmissionStatus::Released->value,
                'grading_status' => \Modules\Learning\Enums\QuizGradingStatus::Released->value,
            ]);
            $result->refresh();

            event(new \Modules\Learning\Events\QuizCompleted($result));

            return $this->success(
                new GradingQueueItemResource($result),
                __('messages.grading.grade_released')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function bulkReleaseGrades(array $submissionIds, array $targets, bool $isAsync): JsonResponse
    {
        try {
            $dto = new BulkOperationDTO(
                submissionIds: $submissionIds,
                targets: $targets,
                performerId: auth('api')->id(),
                async: $isAsync
            );

            $this->bulkService->handleBulkRelease($dto);

            return $this->success(
                ['async' => $isAsync],
                $isAsync ? __('messages.grading.bulk_release_queued') : __('messages.grading.bulk_released')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function bulkApplyFeedback(array $submissionIds, array $targets, string $feedback, bool $isAsync): JsonResponse
    {
        try {
            $dto = new BulkOperationDTO(
                submissionIds: $submissionIds,
                targets: $targets,
                feedback: $feedback,
                performerId: auth('api')->id(),
                async: $isAsync
            );

            $this->bulkService->handleBulkFeedback($dto);

            return $this->success(
                ['async' => $isAsync],
                $isAsync ? __('messages.grading.bulk_feedback_queued') : __('messages.grading.bulk_feedback_applied')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function gradingStatus(Submission $submission): JsonResponse
    {
        return $this->success($this->queueService->getGradingStatusDetails($submission->id));
    }
}
