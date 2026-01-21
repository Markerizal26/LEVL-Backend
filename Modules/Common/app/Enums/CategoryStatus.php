<?php

declare(strict_types=1);

namespace Modules\Common\Enums;

enum CategoryStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

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
            self::Active => __('enums.category_status.active'),
            self::Inactive => __('enums.category_status.inactive'),
        };
    }
}
