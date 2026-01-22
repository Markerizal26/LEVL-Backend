<?php

declare(strict_types=1);

namespace Modules\Learning\Enums;

enum ReviewMode: string
{
    case Immediate = 'immediate';
    case Deferred = 'deferred';
    case Hidden = 'hidden';

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
            self::Immediate => __('enums.review_mode.immediate'),
            self::Deferred => __('enums.review_mode.deferred'),
            self::Hidden => __('enums.review_mode.hidden'),
        };
    }
}
