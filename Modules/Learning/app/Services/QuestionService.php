<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Support\Collection;
use Modules\Grading\Jobs\RecalculateGradesJob;
use Modules\Learning\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Learning\Contracts\Services\QuestionServiceInterface;
use Modules\Learning\Enums\QuestionType;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Events\AnswerKeyChanged;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Question;

class QuestionService implements QuestionServiceInterface
{
    public function __construct(
        private QuestionRepositoryInterface $questionRepository
    ) {}

    public function createQuestion(int $assignmentId, array $data): Question
    {
        $this->validateQuestionData($data);

        $data['assignment_id'] = $assignmentId;

        // Set default order if not provided
        if (! isset($data['order'])) {
            $maxOrder = Question::where('assignment_id', $assignmentId)->max('order') ?? -1;
            $data['order'] = $maxOrder + 1;
        }

        return $this->questionRepository->create($data);
    }

    public function updateQuestion(int $questionId, array $data, ?int $assignmentId = null): Question
    {
        $this->validateQuestionData($data, isUpdate: true);

        if ($assignmentId !== null) {
            $question = $this->questionRepository->find($questionId);
            if (!$question || $question->assignment_id !== $assignmentId) {
                throw new \InvalidArgumentException(__('messages.questions.not_found'));
            }
        }

        return $this->questionRepository->update($questionId, $data);
    }

    public function deleteQuestion(int $questionId, ?int $assignmentId = null): bool
    {
        if ($assignmentId !== null) {
            $question = $this->questionRepository->find($questionId);
            if (!$question || $question->assignment_id !== $assignmentId) {
                throw new \InvalidArgumentException(__('messages.questions.not_found'));
            }
        }

        return $this->questionRepository->delete($questionId);
    }

    public function updateAnswerKey(int $questionId, array $answerKey, int $instructorId): void
    {
        $question = $this->questionRepository->find($questionId);

        if (! $question) {
            throw new \InvalidArgumentException("Question not found: {$questionId}");
        }

        if (! $question->canAutoGrade()) {
            throw new \InvalidArgumentException('Cannot set answer key for manual grading questions');
        }

        $oldAnswerKey = $question->answer_key ?? [];

        $this->questionRepository->update($questionId, ['answer_key' => $answerKey]);

        $question = $this->questionRepository->find($questionId);

        AnswerKeyChanged::dispatch($question, $oldAnswerKey, $answerKey, $instructorId);

        RecalculateGradesJob::dispatch(
            $questionId,
            $oldAnswerKey,
            $answerKey,
            $instructorId
        );
    }

    public function generateQuestionSet(int $assignmentId, ?int $seed = null): Collection
    {
        $assignment = Assignment::findOrFail($assignmentId);
        $seed = $seed ?? random_int(1, PHP_INT_MAX);

        return match ($assignment->randomization_type) {
            RandomizationType::Static => $this->questionRepository->findByAssignment($assignmentId),
            RandomizationType::RandomOrder => $this->getRandomOrderQuestions($assignmentId, $seed),
            RandomizationType::Bank => $this->getBankQuestions($assignment, $seed),
            default => $this->questionRepository->findByAssignment($assignmentId),
        };
    }

    public function getQuestionsByAssignment(int $assignmentId): Collection
    {
        return $this->questionRepository->findByAssignment($assignmentId);
    }

    public function reorderQuestions(int $assignmentId, array $questionIds): void
    {
        $this->questionRepository->reorder($assignmentId, $questionIds);
    }

    private function validateQuestionData(array $data, bool $isUpdate = false): void
    {
        // Weight must be positive
        if (isset($data['weight']) && $data['weight'] <= 0) {
            throw new \InvalidArgumentException('Question weight must be a positive number');
        }

        // Type validation for new questions
        if (! $isUpdate && isset($data['type'])) {
            $type = $data['type'] instanceof QuestionType
                ? $data['type']
                : QuestionType::from($data['type']);

            // MCQ and Checkbox require options
            if ($type->requiresOptions() && empty($data['options'])) {
                throw new \InvalidArgumentException('Options are required for this question type');
            }
        }
    }

    private function getRandomOrderQuestions(int $assignmentId, int $seed): Collection
    {
        $questions = $this->questionRepository->findByAssignment($assignmentId);

        return $questions->shuffle($seed);
    }

    private function getBankQuestions(Assignment $assignment, int $seed): Collection
    {
        $count = $assignment->question_bank_count ?? 10;

        return $this->questionRepository->findRandomFromBank(
            $assignment->id,
            $count,
            $seed
        );
    }
}
