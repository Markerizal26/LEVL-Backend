<?php

namespace Modules\Gamification\Enums;

enum ChallengeType: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Special = 'special';

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
            self::Daily => __('enums.challenge_type.daily'),
            self::Weekly => __('enums.challenge_type.weekly'),
            self::Special => __('enums.challenge_type.special'),
        };
    }
}
