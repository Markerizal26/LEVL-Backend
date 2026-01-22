<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Services;

use Illuminate\Support\Collection;
use Modules\Learning\Models\Question;

interface QuestionServiceInterface
{
    public function createQuestion(int $assignmentId, array $data): Question;

    public function updateQuestion(int $questionId, array $data): Question;

    public function deleteQuestion(int $questionId): bool;

    public function updateAnswerKey(int $questionId, array $answerKey): void;

    public function generateQuestionSet(int $assignmentId, ?int $seed = null): Collection;

    public function getQuestionsByAssignment(int $assignmentId): Collection;

    public function reorderQuestions(int $assignmentId, array $questionIds): void;
}
