<?php

namespace Modules\Content\Enums;

enum TargetType: string
{
    case All = 'all';
    case Role = 'role';
    case Course = 'course';

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
            self::All => __('enums.target_type.all'),
            self::Role => __('enums.target_type.role'),
            self::Course => __('enums.target_type.course'),
        };
    }
}
