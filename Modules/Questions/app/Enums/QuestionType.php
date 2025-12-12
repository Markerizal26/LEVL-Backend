<?php

namespace Modules\Questions\Enums;

enum QuestionType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case ESSAY = 'essay';
    case FILE_UPLOAD = 'file_upload';
    case TRUE_FALSE = 'true_false';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => 'Multiple Choice',
            self::ESSAY => 'Essay',
            self::FILE_UPLOAD => 'File Upload',
            self::TRUE_FALSE => 'True/False',
        };
    }
}
