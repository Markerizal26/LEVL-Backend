<?php

namespace Modules\Common\Enums;

enum SettingType: string
{
    case String = 'string';
    case Number = 'number';
    case Boolean = 'boolean';
    case Json = 'json';

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
            self::String => __('enums.setting_type.string'),
            self::Number => __('enums.setting_type.number'),
            self::Boolean => __('enums.setting_type.boolean'),
            self::Json => __('enums.setting_type.json'),
        };
    }
}
