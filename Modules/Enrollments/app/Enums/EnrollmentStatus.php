<?php

namespace Modules\Enrollments\Enums;

enum EnrollmentStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

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
            self::Pending => __('enums.enrollment_status.pending'),
            self::Active => __('enums.enrollment_status.active'),
            self::Completed => __('enums.enrollment_status.completed'),
            self::Cancelled => __('enums.enrollment_status.cancelled'),
        };
    }
}
