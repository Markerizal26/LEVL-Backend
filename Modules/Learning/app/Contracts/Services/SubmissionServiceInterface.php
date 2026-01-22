<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

interface SubmissionServiceInterface
{
    /**
     * List submissions for an assignment.
     */
    public function listForAssignment(Assignment $assignment, User $user, array $filters = []): Collection;

    /**
     * List submissions by assignment (legacy method).
     *
     * @deprecated Use listForAssignment instead
     */
    public function listByAssignment(Assignment $assignment, array $filters = []);

    /**
     * Create a new submission.
     */
    public function create(Assignment $assignment, int $userId, array $data): Submission;

    /**
     * Update a submission.
     */
    public function update(Submission $submission, array $data): Submission;

    /**
     * Delete a submission.
     */
    public function delete(Submission $submission): bool;

    /**
     * Grade a submission.
     */
    public function grade(Submission $submission, int $score, ?string $feedback = null, ?int $gradedBy = null): Submission;

    /**
     * Get the highest scoring submission for a student on an assignment.
     * Requirements: 8.4, 22.1, 22.2
     */
    public function getHighestScoreSubmission(int $assignmentId, int $studentId): ?Submission;

    /**
     * Start a new submission (creates in_progress state).
     * Requirements: 6.3, 6.4, 7.3, 7.4, 8.3
     */
    public function startSubmission(int $assignmentId, int $studentId): Submission;

    /**
     * Submit answers for a submission.
     * Requirements: 6.3, 6.4
     */
    public function submitAnswers(int $submissionId, array $answers): Submission;

    /**
     * Check attempt limits for a student.
     * Requirements: 7.3, 7.6
     */
    public function checkAttemptLimits(Assignment $assignment, int $studentId): array;

    /**
     * Check attempt limits with override support.
     * Requirements: 7.3, 7.6, 24.2
     */
    public function checkAttemptLimitsWithOverride(Assignment $assignment, int $studentId): array;

    /**
     * Check cooldown period between attempts.
     * Requirements: 7.4
     */
    public function checkCooldownPeriod(Assignment $assignment, int $studentId): array;

    /**
     * Check deadline with override support.
     * Requirements: 6.3, 6.4, 24.3
     */
    public function checkDeadlineWithOverride(Assignment $assignment, int $studentId): bool;

    /**
     * Check if a student has an active override for an assignment.
     * Requirements: 24.1, 24.2, 24.3
     */
    public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool;

    /**
     * Get all submissions for a student on an assignment with highest marked.
     * Requirements: 22.3
     */
    public function getSubmissionsWithHighestMarked(int $assignmentId, int $studentId): Collection;

    /**
     * Search submissions with filters.
     * Requirements: 27.1, 27.2, 27.3, 27.4, 27.5, 27.6
     *
     * @param  string  $query  Search query for student name or email
     * @param  array<string, mixed>  $filters  Optional filters
     * @param  array<string, mixed>  $options  Optional pagination/sorting options
     * @return array{data: Collection, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function searchSubmissions(string $query, array $filters = [], array $options = []): array;
}
