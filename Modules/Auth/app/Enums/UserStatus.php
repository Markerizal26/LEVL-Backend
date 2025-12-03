<?php

namespace Modules\Auth\Enums;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Banned = 'banned';

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
            self::Pending => __('enums.user_status.pending'),
            self::Active => __('enums.user_status.active'),
            self::Inactive => __('enums.user_status.inactive'),
            self::Banned => __('enums.user_status.banned'),
        };
    }
}
