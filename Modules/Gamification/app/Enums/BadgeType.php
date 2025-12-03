<?php

namespace Modules\Gamification\Enums;

enum BadgeType: string
{
    case Achievement = 'achievement';
    case Milestone = 'milestone';
    case Completion = 'completion';

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
            self::Achievement => __('enums.badge_type.achievement'),
            self::Milestone => __('enums.badge_type.milestone'),
            self::Completion => __('enums.badge_type.completion'),
        };
    }
}
