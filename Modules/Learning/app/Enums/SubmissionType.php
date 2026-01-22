<?php

declare(strict_types=1);

namespace Modules\Learning\Enums;

enum SubmissionType: string
{
    case Text = 'text';
    case File = 'file';
    case Mixed = 'mixed';

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
            self::Text => __('enums.submission_type.text'),
            self::File => __('enums.submission_type.file'),
            self::Mixed => __('enums.submission_type.mixed'),
        };
    }
}
