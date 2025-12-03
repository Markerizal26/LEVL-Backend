<?php

namespace Modules\Gamification\Enums;

enum PointSourceType: string
{
    case Lesson = 'lesson';
    case Assignment = 'assignment';
    case Attempt = 'attempt';
    case System = 'system';

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
            self::Lesson => __('enums.point_source_type.lesson'),
            self::Assignment => __('enums.point_source_type.assignment'),
            self::Attempt => __('enums.point_source_type.attempt'),
            self::System => __('enums.point_source_type.system'),
        };
    }
}
