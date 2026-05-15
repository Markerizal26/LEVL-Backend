<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Modules\Learning\Enums\QuizQuestionType;
use Modules\Learning\Models\QuizAnswer;
use Modules\Learning\Models\QuizQuestion;

class QuizObjectiveGrader
{
    public function grade(QuizQuestion $question, ?QuizAnswer $answer): float
    {
        if (! $answer) {
            return 0.0;
        }

        $answerKey = $question->answer_key ?? [];
        $weight    = (float) $question->weight;

        return match ($question->type) {
            QuizQuestionType::MultipleChoice => $this->gradeMultipleChoice($answer->selected_options, $answerKey, $weight),
            QuizQuestionType::TrueFalse      => $this->gradeTrueFalse($answer->selected_options, $answerKey, $weight),
            QuizQuestionType::Checkbox       => $this->gradeCheckbox($answer->selected_options, $answerKey, $weight),
            default                          => 0.0,
        };
    }

    private function gradeMultipleChoice(?array $selected, array $answerKey, float $weight): float
    {
        if (empty($selected)) {
            return 0.0;
        }

        $correctKey = $answerKey[0] ?? null;

        return (string) ($selected[0] ?? '') === (string) $correctKey ? $weight : 0.0;
    }

    private function gradeTrueFalse(?array $selected, array $answerKey, float $weight): float
    {
        if (empty($selected)) {
            return 0.0;
        }

        $correctAnswer = $answerKey[0] ?? null;
        $studentAnswer = $selected[0] ?? null;

        $correct = filter_var($correctAnswer, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $student = filter_var($studentAnswer, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($correct === null || $student === null) {
            return 0.0;
        }

        return $correct === $student ? $weight : 0.0;
    }

    private function gradeCheckbox(?array $selected, array $answerKey, float $weight): float
    {
        if (empty($selected) && empty($answerKey)) {
            return $weight;
        }

        if (empty($selected) || empty($answerKey)) {
            return 0.0;
        }

        $selectedSorted = array_map('strval', array_values($selected));
        sort($selectedSorted);
        $keySorted = array_map('strval', array_values($answerKey));
        sort($keySorted);

        return $selectedSorted === $keySorted ? $weight : 0.0;
    }
}
