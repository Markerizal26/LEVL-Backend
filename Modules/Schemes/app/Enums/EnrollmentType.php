<?php

namespace Modules\Schemes\Enums;

enum EnrollmentType: string
{
    case AutoAccept = 'auto_accept';
    case KeyBased = 'key_based';
    case Approval = 'approval';

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
            self::AutoAccept => __('enums.enrollment_type.auto_accept'),
            self::KeyBased => __('enums.enrollment_type.key_based'),
            self::Approval => __('enums.enrollment_type.approval'),
        };
    }
}
