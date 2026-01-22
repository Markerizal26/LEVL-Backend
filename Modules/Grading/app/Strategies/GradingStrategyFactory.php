<?php

declare(strict_types=1);

namespace Modules\Grading\Strategies;

use Modules\Grading\Contracts\GradingStrategyInterface;
use Modules\Learning\Enums\QuestionType;

class GradingStrategyFactory
{
    public static function make(QuestionType $type): GradingStrategyInterface
    {
        return match ($type) {
            QuestionType::MultipleChoice => new MultipleChoiceGradingStrategy,
            QuestionType::Checkbox => new CheckboxGradingStrategy,
            QuestionType::Essay, QuestionType::FileUpload => new ManualGradingStrategy,
        };
    }
}
