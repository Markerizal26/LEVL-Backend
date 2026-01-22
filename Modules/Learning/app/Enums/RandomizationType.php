<?php

declare(strict_types=1);

namespace Modules\Learning\Enums;

enum RandomizationType: string
{
    case Static = 'static';
    case RandomOrder = 'random_order';
    case Bank = 'bank';

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
            self::Static => __('enums.randomization_type.static'),
            self::RandomOrder => __('enums.randomization_type.random_order'),
            self::Bank => __('enums.randomization_type.bank'),
        };
    }
}
