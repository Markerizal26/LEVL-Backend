<?php

namespace Modules\Assessments\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

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
            self::Pending => __('enums.registration_status.pending'),
            self::Confirmed => __('enums.registration_status.confirmed'),
            self::Completed => __('enums.registration_status.completed'),
            self::Cancelled => __('enums.registration_status.cancelled'),
            self::NoShow => __('enums.registration_status.no_show'),
        };
    }
}
