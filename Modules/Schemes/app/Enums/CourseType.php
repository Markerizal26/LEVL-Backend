<?php

namespace Modules\Schemes\Enums;

enum CourseType: string
{
    case Okupasi = 'okupasi';
    case Kluster = 'kluster';

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
            self::Okupasi => __('enums.course_type.okupasi'),
            self::Kluster => __('enums.course_type.kluster'),
        };
    }
}
