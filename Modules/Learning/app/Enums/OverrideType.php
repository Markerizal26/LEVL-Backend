<?php

declare(strict_types=1);

namespace Modules\Learning\Enums;

enum OverrideType: string
{
    case Prerequisite = 'prerequisite';
    case Deadline = 'deadline';
    case Attempts = 'attempts';

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
            self::Prerequisite => __('enums.override_type.prerequisite'),
            self::Deadline => __('enums.override_type.deadline'),
            self::Attempts => __('enums.override_type.attempts'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Prerequisite => __('enums.override_type_desc.prerequisite'),
            self::Deadline => __('enums.override_type_desc.deadline'),
            self::Attempts => __('enums.override_type_desc.attempts'),
        };
    }
}
