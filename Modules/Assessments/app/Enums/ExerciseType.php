<?php

namespace Modules\Assessments\Enums;

enum ExerciseType: string
{
    case Quiz = 'quiz';
    case Exam = 'exam';

    /**
     * Get all enum values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get enum for validation rules.
     */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Quiz => __('enums.exercise_type.quiz'),
            self::Exam => __('enums.exercise_type.exam'),
        };
    }
}
