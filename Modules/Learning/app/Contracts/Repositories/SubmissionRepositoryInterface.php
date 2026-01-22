<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

interface SubmissionRepositoryInterface
{
    public function listForAssignment(Assignment $assignment, array $filters = []): Collection;

    public function findByUserAndAssignment(int $userId, int $assignmentId): ?Submission;

    public function create(array $attributes): Submission;

    public function update(Submission $submission, array $attributes): Submission;

    public function delete(Submission $submission): bool;

    /**
     * Find the highest scoring submission for a student on an assignment.
     * Requirements: 8.4, 22.1, 22.2
     */
    public function findHighestScore(int $studentId, int $assignmentId): ?Submission;

    /**
     * Find all submissions for a student on an assignment.
     * Requirements: 22.1
     */
    public function findByStudentAndAssignment(int $studentId, int $assignmentId): Collection;

    /**
     * Count attempts for a student on an assignment.
     * Requirements: 7.3
     */
    public function countAttempts(int $studentId, int $assignmentId): int;

    /**
     * Get the last submission time for a student on an assignment.
     * Requirements: 7.4
     */
    public function getLastSubmissionTime(int $studentId, int $assignmentId): ?\Illuminate\Support\Carbon;

    /**
     * Search submissions using Laravel Scout with Meilisearch.
     *
     * Supports full-text search by student name or email, with additional filters
     * for state, score range, date range, and assignment.
     *
     * Requirements: 27.1, 27.2, 27.3, 27.4, 27.6
     *
     * @param  string  $query  Search query for student name or email
     * @param  array<string, mixed>  $filters  Optional filters:
     *                                         - state: string (submission state)
     *                                         - score_min: float (minimum score)
     *                                         - score_max: float (maximum score)
     *                                         - date_from: string (start date Y-m-d)
     *                                         - date_to: string (end date Y-m-d)
     *                                         - assignment_id: int (filter by assignment)
     * @param  array<string, mixed>  $options  Optional pagination/sorting options:
     *                                         - page: int (page number, default 1)
     *                                         - per_page: int (items per page, default 15)
     *                                         - sort_by: string (field to sort by)
     *                                         - sort_direction: string (asc or desc)
     * @return array{
     *     data: Collection,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int
     * }
     */
    public function search(string $query, array $filters = [], array $options = []): array;

    /**
     * Filter submissions by state.
     *
     * Requirements: 27.2
     *
     * @param  string  $state  The submission state to filter by
     * @return Collection<int, Submission>
     */
    public function filterByState(string $state): Collection;

    /**
     * Filter submissions by score range.
     *
     * Requirements: 27.3
     *
     * @param  float  $min  Minimum score (inclusive)
     * @param  float  $max  Maximum score (inclusive)
     * @return Collection<int, Submission>
     */
    public function filterByScoreRange(float $min, float $max): Collection;

    /**
     * Filter submissions by submission date range.
     *
     * Requirements: 27.4
     *
     * @param  string  $from  Start date (Y-m-d format)
     * @param  string  $to  End date (Y-m-d format)
     * @return Collection<int, Submission>
     */
    public function filterByDateRange(string $from, string $to): Collection;
}
