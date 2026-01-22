<?php

declare(strict_types=1);

namespace Modules\Grading\Strategies;

use Modules\Grading\Contracts\GradingStrategyInterface;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Question;

class CheckboxGradingStrategy implements GradingStrategyInterface
{
    public function grade(Question $question, Answer $answer): ?float
    {
        $answerKey = $question->answer_key ?? [];
        $selectedOptions = $answer->selected_options ?? [];

        // Normalize to arrays
        $correctSet = is_array($answerKey) ? $answerKey : [$answerKey];
        $selectedSet = is_array($selectedOptions) ? $selectedOptions : [$selectedOptions];

        // Sort both arrays for comparison
        sort($correctSet);
        sort($selectedSet);

        // Set equality check - must match exactly
        if ($correctSet === $selectedSet) {
            return (float) $question->max_score;
        }

        return 0.0;
    }

    public function canAutoGrade(): bool
    {
        return true;
    }
}
