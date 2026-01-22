<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\Grading\Contracts\Services\GradingServiceInterface;
use Modules\Grading\Http\Requests\BulkFeedbackRequest;
use Modules\Grading\Http\Requests\BulkReleaseGradesRequest;
use Modules\Grading\Http\Requests\GradingQueueRequest;
use Modules\Grading\Http\Requests\ManualGradeRequest;
use Modules\Grading\Http\Requests\OverrideGradeRequest;
use Modules\Grading\Http\Requests\SaveDraftGradeRequest;
use Modules\Grading\Http\Resources\DraftGradeResource;
use Modules\Grading\Http\Resources\GradeResource;
use Modules\Grading\Http\Resources\GradingQueueItemResource;
use Modules\Grading\Jobs\BulkApplyFeedbackJob;
use Modules\Grading\Jobs\BulkReleaseGradesJob;
use Modules\Learning\Models\Submission;

/**
 * Controller for grading operations.
 *
 * Handles:
 * - Auto-grading submissions (Requirement 3.5, 3.6, 3.7)
 * - Manual grading workflow (Requirements 10.1, 12.1, 12.2)
 * - Grading queue with filters (Requirements 10.1, 10.2, 10.3, 10.4)
 * - Draft grade saving (Requirements 11.1, 11.2, 11.3)
 * - Grade override (Requirements 16.1, 16.2, 16.3)
 * - Bulk operations (Requirements 26.2, 26.4, 26.5)
 *
 * @tags Grading
 */
class GradingController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly GradingServiceInterface $gradingService
    ) {}

    // =========================================================================
    // Auto-Grading (Requirements 3.5, 3.6, 3.7)
    // =========================================================================

    /**
     * Trigger auto-grading for a submission.
     *
     * POST /submissions/{submission}/auto-grade
     *
     * Auto-grades all auto-gradable questions (MCQ, Checkbox) and
     * marks manual questions (Essay, File Upload) for manual grading.
     *
     * Requirements: 3.5, 3.6, 3.7, 23.1
     *
     * @authenticated
     */
    public function autoGrade(Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can trigger auto-grading
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        try {
            $this->gradingService->autoGrade($submission->id);

            $submission->refresh();
            $submission->load(['grade', 'answers.question']);

            return $this->success([
                'submission' => [
                    'id' => $submission->id,
                    'state' => $submission->state?->value,
                    'score' => $submission->score,
                ],
                'grade' => $submission->grade ? GradeResource::make($submission->grade) : null,
            ], __('messages.grading.auto_graded'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    // =========================================================================
    // Manual Grading (Requirements 10.1, 12.1, 12.2)
    // =========================================================================

    /**
     * Submit manual grades for a submission.
     *
     * POST /submissions/{submission}/manual-grade
     *
     * Allows instructors to grade essay and file upload questions
     * with partial credit support.
     *
     * Requirements: 10.1, 12.1, 12.2, 12.3
     *
     * @authenticated
     */
    public function manualGrade(ManualGradeRequest $request, Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can manually grade
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        try {
            $validated = $request->validated();

            // Transform grades array to keyed format expected by service
            $grades = collect($validated['grades'])->keyBy('question_id')->toArray();

            $grade = $this->gradingService->manualGrade(
                $submission->id,
                $grades,
                $validated['feedback'] ?? null
            );

            $grade->load(['submission', 'user', 'grader']);

            return $this->success([
                'grade' => GradeResource::make($grade),
            ], __('messages.grading.manual_graded'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    // =========================================================================
    // Grading Queue (Requirements 10.1, 10.2, 10.3, 10.4)
    // =========================================================================

    /**
     * Get the grading queue with filters.
     *
     * GET /grading/queue
     *
     * Returns submissions pending manual grading, ordered by submission
     * timestamp (oldest first) with metadata including student name,
     * assignment title, and questions requiring grading.
     *
     * Requirements: 10.1, 10.2, 10.3, 10.4
     *
     * @authenticated
     */
    public function queue(GradingQueueRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can view grading queue
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        $validated = $request->validated();

        $filters = array_filter([
            'assignment_id' => $validated['assignment_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ], fn ($value) => $value !== null);

        $queue = $this->gradingService->getGradingQueue($filters);

        // Apply pagination
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 15);
        $total = $queue->count();
        $paginatedQueue = $queue->slice(($page - 1) * $perPage, $perPage)->values();

        return $this->success([
            'queue' => GradingQueueItemResource::collection($paginatedQueue),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Return a submission to the grading queue.
     *
     * POST /submissions/{submission}/return-to-queue
     *
     * Allows instructors to return a graded submission back to the
     * pending manual grading queue.
     *
     * Requirements: 10.6
     *
     * @authenticated
     */
    public function returnToQueue(Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can return to queue
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        try {
            $this->gradingService->returnToQueue($submission->id);

            $submission->refresh();

            return $this->success([
                'submission' => [
                    'id' => $submission->id,
                    'state' => $submission->state?->value,
                ],
            ], __('messages.grading.returned_to_queue'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    // =========================================================================
    // Draft Grade Saving (Requirements 11.1, 11.2, 11.3)
    // =========================================================================

    /**
     * Save draft grades for a submission.
     *
     * POST /submissions/{submission}/draft-grade
     *
     * Allows instructors to save grading progress without finalizing.
     * Does not change submission state.
     *
     * Requirements: 11.1, 11.2
     *
     * @authenticated
     */
    public function saveDraftGrade(SaveDraftGradeRequest $request, Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can save draft grades
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        try {
            $validated = $request->validated();

            // Transform grades array to keyed format expected by service
            $grades = collect($validated['grades'])->keyBy('question_id')->toArray();

            $this->gradingService->saveDraftGrade($submission->id, $grades);

            return $this->success([
                'submission_id' => $submission->id,
                'message' => 'Draft grades saved successfully.',
            ], __('messages.grading.draft_saved'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get draft grades for a submission.
     *
     * GET /submissions/{submission}/draft-grade
     *
     * Retrieves previously saved draft grades for resuming grading.
     *
     * Requirements: 11.3
     *
     * @authenticated
     */
    public function getDraftGrade(Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can view draft grades
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        $draftGrade = $this->gradingService->getDraftGrade($submission->id);

        if ($draftGrade === null) {
            return $this->success([
                'draft_grade' => null,
                'message' => 'No draft grades found for this submission.',
            ]);
        }

        return $this->success([
            'draft_grade' => DraftGradeResource::make($draftGrade),
        ]);
    }

    // =========================================================================
    // Grade Override (Requirements 16.1, 16.2, 16.3)
    // =========================================================================

    /**
     * Override a submission's grade.
     *
     * POST /submissions/{submission}/override-grade
     *
     * Allows instructors to manually override the final grade with
     * a required justification. Preserves the original calculated score.
     *
     * Requirements: 16.1, 16.2, 16.3
     *
     * @authenticated
     */
    public function overrideGrade(OverrideGradeRequest $request, Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can override grades
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        try {
            $validated = $request->validated();

            $this->gradingService->overrideGrade(
                $submission->id,
                (float) $validated['score'],
                $validated['reason']
            );

            $submission->refresh();
            $submission->load('grade');

            return $this->success([
                'submission' => [
                    'id' => $submission->id,
                    'score' => $submission->score,
                ],
                'grade' => $submission->grade ? GradeResource::make($submission->grade) : null,
            ], __('messages.grading.grade_overridden'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    // =========================================================================
    // Grade Release (Requirements 14.6)
    // =========================================================================

    /**
     * Release a grade to make it visible to the student.
     *
     * POST /submissions/{submission}/release-grade
     *
     * Transitions the submission to released state and triggers
     * notifications to the student.
     *
     * Requirements: 14.6
     *
     * @authenticated
     */
    public function releaseGrade(Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can release grades
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        try {
            $this->gradingService->releaseGrade($submission->id);

            $submission->refresh();
            $submission->load('grade');

            return $this->success([
                'submission' => [
                    'id' => $submission->id,
                    'state' => $submission->state?->value,
                ],
                'grade' => $submission->grade ? GradeResource::make($submission->grade) : null,
            ], __('messages.grading.grade_released'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    // =========================================================================
    // Bulk Operations (Requirements 26.2, 26.4, 26.5, 28.6)
    // =========================================================================

    /**
     * Bulk release grades for multiple submissions.
     *
     * POST /grading/bulk-release
     *
     * Releases grades for multiple submissions at once. Validates
     * all submissions before execution and reports any errors.
     *
     * Supports async processing via the `async` parameter to dispatch
     * the operation as a background job (Requirements 28.6).
     *
     * Requirements: 26.2, 26.5, 28.6
     *
     * @authenticated
     */
    public function bulkReleaseGrades(BulkReleaseGradesRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can bulk release grades
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        try {
            $validated = $request->validated();
            $submissionIds = $validated['submission_ids'];
            $async = $validated['async'] ?? false;

            // If async mode is requested, dispatch job and return immediately (Requirements 28.6)
            if ($async) {
                // Validate before dispatching to fail fast on obvious errors
                $validation = $this->gradingService->validateBulkReleaseGrades($submissionIds);

                if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
                    return $this->error(
                        'Bulk release validation failed: '.implode('; ', $validation['errors']),
                        ['errors' => $validation['errors']],
                        422
                    );
                }

                BulkReleaseGradesJob::dispatch($submissionIds, $user->id);

                return $this->success([
                    'message' => 'Bulk grade release job has been queued for processing.',
                    'submission_count' => count($submissionIds),
                    'async' => true,
                ], __('messages.grading.bulk_release_queued'));
            }

            // Synchronous processing
            $result = $this->gradingService->bulkReleaseGrades($submissionIds);

            return $this->success([
                'success_count' => $result['success'],
                'failed_count' => $result['failed'],
                'released_submissions' => $result['submissions']->pluck('id'),
                'errors' => $result['errors'],
                'async' => false,
            ], __('messages.grading.bulk_released'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    /**
     * Bulk apply feedback to multiple submissions.
     *
     * POST /grading/bulk-feedback
     *
     * Applies the same feedback text to multiple submissions at once.
     * Validates all submissions before execution and reports any errors.
     *
     * Supports async processing via the `async` parameter to dispatch
     * the operation as a background job (Requirements 28.6).
     *
     * Requirements: 26.4, 26.5, 28.6
     *
     * @authenticated
     */
    public function bulkApplyFeedback(BulkFeedbackRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can bulk apply feedback
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        try {
            $validated = $request->validated();
            $submissionIds = $validated['submission_ids'];
            $feedback = $validated['feedback'];
            $async = $validated['async'] ?? false;

            // If async mode is requested, dispatch job and return immediately (Requirements 28.6)
            if ($async) {
                // Validate before dispatching to fail fast on obvious errors
                $validation = $this->gradingService->validateBulkApplyFeedback($submissionIds);

                if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
                    return $this->error(
                        'Bulk feedback validation failed: '.implode('; ', $validation['errors']),
                        ['errors' => $validation['errors']],
                        422
                    );
                }

                BulkApplyFeedbackJob::dispatch($submissionIds, $feedback, $user->id);

                return $this->success([
                    'message' => 'Bulk feedback application job has been queued for processing.',
                    'submission_count' => count($submissionIds),
                    'async' => true,
                ], __('messages.grading.bulk_feedback_queued'));
            }

            // Synchronous processing
            $result = $this->gradingService->bulkApplyFeedback($submissionIds, $feedback);

            return $this->success([
                'success_count' => $result['success'],
                'failed_count' => $result['failed'],
                'updated_submissions' => $result['submissions']->pluck('id'),
                'errors' => $result['errors'],
                'async' => false,
            ], __('messages.grading.bulk_feedback_applied'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    // =========================================================================
    // Grading Validation (Requirements 11.4, 11.5)
    // =========================================================================

    /**
     * Check if grading is complete for a submission.
     *
     * GET /submissions/{submission}/grading-status
     *
     * Returns whether all required questions have been graded.
     *
     * Requirements: 11.4, 11.5
     *
     * @authenticated
     */
    public function gradingStatus(Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can check grading status
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.grading.no_access'));
        }

        $isComplete = $this->gradingService->validateGradingComplete($submission->id);

        $submission->load(['answers.question', 'grade']);

        $gradedCount = $submission->answers->filter(fn ($a) => $a->score !== null)->count();
        $totalCount = $submission->answers->count();

        return $this->success([
            'submission_id' => $submission->id,
            'is_complete' => $isComplete,
            'graded_questions' => $gradedCount,
            'total_questions' => $totalCount,
            'can_finalize' => $isComplete,
            'can_release' => $isComplete && $submission->grade && ! $submission->grade->is_draft,
        ]);
    }
}
