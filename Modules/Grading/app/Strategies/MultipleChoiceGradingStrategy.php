<?php

declare(strict_types=1);

namespace Modules\Grading\Strategies;

use Modules\Grading\Contracts\GradingStrategyInterface;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Question;

class MultipleChoiceGradingStrategy implements GradingStrategyInterface
{
    public function grade(Question $question, Answer $answer): ?float
    {
        $answerKey = $question->answer_key;
        $selectedOptions = $answer->selected_options ?? [];

        if (empty($answerKey) || empty($selectedOptions)) {
            return 0.0;
        }

        // MCQ should have exactly one correct answer
        $correctAnswer = is_array($answerKey) ? ($answerKey[0] ?? null) : $answerKey;
        $selectedAnswer = is_array($selectedOptions) ? ($selectedOptions[0] ?? null) : $selectedOptions;

        // Exact match required
        if ($correctAnswer === $selectedAnswer) {
            return (float) $question->max_score;
        }

        return 0.0;
    }

    public function canAutoGrade(): bool
    {
        return true;
    }
}
