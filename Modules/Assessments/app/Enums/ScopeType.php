<?php

namespace Modules\Assessments\Enums;

enum ScopeType: string
{
    case Course = 'course';
    case Unit = 'unit';
    case Lesson = 'lesson';

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
            self::Course => __('enums.scope_type.course'),
            self::Unit => __('enums.scope_type.unit'),
            self::Lesson => __('enums.scope_type.lesson'),
        };
    }
}
