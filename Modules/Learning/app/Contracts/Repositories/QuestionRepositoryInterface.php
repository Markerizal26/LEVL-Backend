<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Learning\Models\Question;

interface QuestionRepositoryInterface
{
    public function create(array $data): Question;

    public function updateQuestion(int $id, array $data): Question;

    public function deleteQuestion(int $id): bool;

    public function find(int $id): ?Question;

    public function findByAssignment(int $assignmentId): Collection;

    public function findRandomFromBank(int $assignmentId, int $count, int $seed): Collection;

    public function reorder(int $assignmentId, array $questionIds): void;

    public function findWithDetails(int $id): ?Question;

    public function findManualGradingQuestions(int $assignmentId): Collection;

    public function findAutoGradableQuestions(int $assignmentId): Collection;

    public function invalidateQuestionCache(int $id): void;

    public function invalidateAssignmentQuestionsCache(int $assignmentId): void;
}
