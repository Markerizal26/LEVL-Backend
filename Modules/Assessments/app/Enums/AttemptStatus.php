<?php

namespace Modules\Assessments\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Expired = 'expired';

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
            self::InProgress => __('enums.attempt_status.in_progress'),
            self::Completed => __('enums.attempt_status.completed'),
            self::Expired => __('enums.attempt_status.expired'),
        };
    }
}
