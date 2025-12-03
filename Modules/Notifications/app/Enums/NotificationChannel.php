<?php

namespace Modules\Notifications\Enums;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Push = 'push';

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
            self::InApp => __('enums.notification_channel.in_app'),
            self::Email => __('enums.notification_channel.email'),
            self::Push => __('enums.notification_channel.push'),
        };
    }
}
