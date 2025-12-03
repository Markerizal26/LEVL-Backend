<?php

namespace Modules\Schemes\Enums;

enum LevelTag: string
{
    case Dasar = 'dasar';
    case Menengah = 'menengah';
    case Mahir = 'mahir';

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
            self::Dasar => __('enums.level_tag.dasar'),
            self::Menengah => __('enums.level_tag.menengah'),
            self::Mahir => __('enums.level_tag.mahir'),
        };
    }
}
