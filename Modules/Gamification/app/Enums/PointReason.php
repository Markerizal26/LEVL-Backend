<?php

namespace Modules\Gamification\Enums;

enum PointReason: string
{
    case Completion = 'completion';
    case Score = 'score';
    case Bonus = 'bonus';
    case Penalty = 'penalty';

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
            self::Completion => __('enums.point_reason.completion'),
            self::Score => __('enums.point_reason.score'),
            self::Bonus => __('enums.point_reason.bonus'),
            self::Penalty => __('enums.point_reason.penalty'),
        };
    }
}
