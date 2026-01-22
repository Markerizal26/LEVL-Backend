<?php

declare(strict_types=1);

namespace Modules\Grading\Contracts;

use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Question;

interface GradingStrategyInterface
{
    /**
     * Grade an answer for a question.
     *
     * @return float|null Score (null if manual grading required)
     */
    public function grade(Question $question, Answer $answer): ?float;

    /**
     * Check if this strategy can auto-grade.
     */
    public function canAutoGrade(): bool;
}
