<?php

declare(strict_types=1);

namespace Modules\Learning\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case Checkbox = 'checkbox';
    case Essay = 'essay';
    case FileUpload = 'file_upload';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => __('enums.question_type.multiple_choice'),
            self::Checkbox => __('enums.question_type.checkbox'),
            self::Essay => __('enums.question_type.essay'),
            self::FileUpload => __('enums.question_type.file_upload'),
        };
    }

    public function canAutoGrade(): bool
    {
        return match ($this) {
            self::MultipleChoice, self::Checkbox => true,
            self::Essay, self::FileUpload => false,
        };
    }

    public function requiresOptions(): bool
    {
        return match ($this) {
            self::MultipleChoice, self::Checkbox => true,
            self::Essay, self::FileUpload => false,
        };
    }
}
