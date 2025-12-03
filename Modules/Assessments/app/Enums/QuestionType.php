<?php

namespace Modules\Assessments\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case FreeText = 'free_text';
    case FileUpload = 'file_upload';

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
            self::MultipleChoice => __('enums.question_type.multiple_choice'),
            self::FreeText => __('enums.question_type.free_text'),
            self::FileUpload => __('enums.question_type.file_upload'),
        };
    }
}
