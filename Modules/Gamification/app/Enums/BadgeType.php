<?php

namespace Modules\Gamification\Enums;

enum BadgeType: string
{
    case Achievement = 'achievement';
    case Milestone = 'milestone';
    case Completion = 'completion';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    public function label(): string
    {
        return match ($this) {
            self::Achievement => __('enums.badge_type.achievement'),
            self::Milestone => __('enums.badge_type.milestone'),
            self::Completion => __('enums.badge_type.completion'),
        };
    }
}
