<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Learning\Models\Question;

interface QuestionRepositoryInterface
{
    public function create(array $data): Question;

    public function update(int $id, array $data): Question;

    public function delete(int $id): bool;

    public function find(int $id): ?Question;

    public function findByAssignment(int $assignmentId): Collection;

    public function findRandomFromBank(int $assignmentId, int $count, int $seed): Collection;

    public function reorder(int $assignmentId, array $questionIds): void;

    /**
     * Find a question with all related data for detailed view.
     * Requirements: 28.5
     */
    public function findWithDetails(int $id): ?Question;

    /**
     * Find questions that need manual grading for an assignment with caching.
     * Requirements: 28.5, 28.7
     */
    public function findManualGradingQuestions(int $assignmentId): Collection;

    /**
     * Find questions that can be auto-graded with caching.
     * Requirements: 28.5, 28.7
     */
    public function findAutoGradableQuestions(int $assignmentId): Collection;

    /**
     * Invalidate cache for a single question.
     * Requirements: 28.7
     */
    public function invalidateQuestionCache(int $id): void;

    /**
     * Invalidate all question caches for an assignment.
     * Requirements: 28.7
     */
    public function invalidateAssignmentQuestionsCache(int $assignmentId): void;
}
