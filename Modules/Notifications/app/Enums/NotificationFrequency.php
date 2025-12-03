<?php

namespace Modules\Notifications\Enums;

enum NotificationFrequency: string
{
    case Immediate = 'immediate';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Never = 'never';

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
            self::Immediate => __('enums.notification_frequency.immediate'),
            self::Daily => __('enums.notification_frequency.daily'),
            self::Weekly => __('enums.notification_frequency.weekly'),
            self::Never => __('enums.notification_frequency.never'),
        };
    }
}
