<?php

declare(strict_types=1);

namespace Modules\Learning\Enums;

enum SubmissionType: string
{
    case Text = 'text';
    case File = 'file';
    case Mixed = 'mixed';

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
            self::Text => __('enums.submission_type.text'),
            self::File => __('enums.submission_type.file'),
            self::Mixed => __('enums.submission_type.mixed'),
        };
    }
}
