<?php

namespace Modules\Assessments\Enums;

enum RegistrationResult: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case Pending = 'pending';

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
            self::Passed => __('enums.registration_result.passed'),
            self::Failed => __('enums.registration_result.failed'),
            self::Pending => __('enums.registration_result.pending'),
        };
    }
}
