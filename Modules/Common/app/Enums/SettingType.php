<?php

declare(strict_types=1);

namespace Modules\Common\Enums;

enum SettingType: string
{
    case String = 'string';
    case Number = 'number';
    case Boolean = 'boolean';
    case Json = 'json';

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
            self::String => __('enums.setting_type.string'),
            self::Number => __('enums.setting_type.number'),
            self::Boolean => __('enums.setting_type.boolean'),
            self::Json => __('enums.setting_type.json'),
        };
    }
}
