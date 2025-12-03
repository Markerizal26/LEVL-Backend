<?php

namespace Modules\Grading\Enums;

enum SourceType: string
{
    case Assignment = 'assignment';
    case Attempt = 'attempt';

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
            self::Assignment => __('enums.source_type.assignment'),
            self::Attempt => __('enums.source_type.attempt'),
        };
    }
}
