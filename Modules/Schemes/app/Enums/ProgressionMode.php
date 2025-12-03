<?php

namespace Modules\Schemes\Enums;

enum ProgressionMode: string
{
    case Sequential = 'sequential';
    case Free = 'free';

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
            self::Sequential => __('enums.progression_mode.sequential'),
            self::Free => __('enums.progression_mode.free'),
        };
    }
}
