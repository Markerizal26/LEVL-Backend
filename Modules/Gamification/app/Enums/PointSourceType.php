<?php

namespace Modules\Gamification\Enums;

enum PointSourceType: string
{
    case Lesson = 'lesson';
    case Assignment = 'assignment';
    case Attempt = 'attempt';
    case Challenge = 'challenge';
    case System = 'system';
    case Grade = 'grade';

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
            self::Lesson => __('enums.point_source_type.lesson'),
            self::Assignment => __('enums.point_source_type.assignment'),
            self::Attempt => __('enums.point_source_type.attempt'),
            self::Challenge => __('enums.point_source_type.challenge'),
            self::System => __('enums.point_source_type.system'),
            self::Grade => 'Grade Achievement',
        };
    }
}
