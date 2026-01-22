<?php

declare(strict_types=1);

namespace Modules\Grading\Contracts\Services;

use Illuminate\Support\Collection;
use Modules\Grading\Models\Grade;

interface GradingServiceInterface
{
    public function autoGrade(int $submissionId): void;

    public function manualGrade(int $submissionId, array $grades, ?string $feedback = null): Grade;

    public function saveDraftGrade(int $submissionId, array $partialGrades): void;

    public function getDraftGrade(int $submissionId): ?array;

    public function calculateScore(int $submissionId): float;

    public function recalculateAfterAnswerKeyChange(int $questionId): void;

    public function overrideGrade(int $submissionId, float $score, string $reason): void;

    public function getGradingQueue(array $filters = []): Collection;

    public function returnToQueue(int $submissionId): void;

    public function validateGradingComplete(int $submissionId): bool;

    public function releaseGrade(int $submissionId): void;

    /**
     * Validate submissions before bulk grade release.
     *
     * Requirements: 26.5 - THE System SHALL validate bulk operations before execution and report any errors
     *
     * @param  array<int>  $submissionIds  Array of submission IDs to validate
     * @return array{valid: bool, errors: array<string>} Validation result with errors if any
     */
    public function validateBulkReleaseGrades(array $submissionIds): array;

    /**
     * Bulk release grades for multiple submissions.
     *
     * Dispatches GradesReleased event to trigger notifications for all released submissions.
     * Requirements: 14.6 - WHEN instructor releases grades in deferred mode, THE System SHALL notify students
     * Requirements: 26.2 - THE System SHALL support bulk grade release for submissions in deferred review mode
     * Requirements: 26.5 - THE System SHALL validate bulk operations before execution and report any errors
     *
     * @param  array<int>  $submissionIds  Array of submission IDs to release
     * @return array{success: int, failed: int, submissions: Collection<int, \Modules\Learning\Models\Submission>, errors: array<string>} Result with success/failure counts
     *
     * @throws \InvalidArgumentException if validation fails for all submissions
     */
    public function bulkReleaseGrades(array $submissionIds): array;

    /**
     * Validate submissions before bulk feedback application.
     *
     * Requirements: 26.5 - THE System SHALL validate bulk operations before execution and report any errors
     *
     * @param  array<int>  $submissionIds  Array of submission IDs to validate
     * @return array{valid: bool, errors: array<string>} Validation result with errors if any
     */
    public function validateBulkApplyFeedback(array $submissionIds): array;

    /**
     * Bulk apply feedback to multiple submissions.
     *
     * Requirements: 26.4 - THE System SHALL support bulk feedback application to selected submissions
     * Requirements: 26.5 - THE System SHALL validate bulk operations before execution and report any errors
     *
     * @param  array<int>  $submissionIds  Array of submission IDs to apply feedback to
     * @param  string  $feedback  The feedback text to apply to all submissions
     * @return array{success: int, failed: int, submissions: Collection<int, \Modules\Learning\Models\Submission>, errors: array<string>} Result with success/failure counts
     *
     * @throws \InvalidArgumentException if validation fails for all submissions or feedback is empty
     */
    public function bulkApplyFeedback(array $submissionIds, string $feedback): array;

    /**
     * Recalculate course grade for a student.
     * Uses highest scores from all assignments in the course.
     * Requirements: 22.2, 22.5
     */
    public function recalculateCourseGrade(int $studentId, int $courseId): ?float;

    /**
     * Calculate course grade using highest scores for each assignment.
     * Requirements: 22.2
     */
    public function calculateCourseGrade(int $studentId, int $courseId): float;
}
