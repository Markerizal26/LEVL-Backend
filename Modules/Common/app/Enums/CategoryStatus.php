<?php

namespace Modules\Common\Enums;

enum CategoryStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

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
            self::Active => __('enums.category_status.active'),
            self::Inactive => __('enums.category_status.inactive'),
        };
    }
}
