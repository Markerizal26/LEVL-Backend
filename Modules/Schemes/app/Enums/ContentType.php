<?php

namespace Modules\Schemes\Enums;

enum ContentType: string
{
    case Markdown = 'markdown';
    case Video = 'video';
    case Link = 'link';

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
            self::Markdown => __('enums.content_type.markdown'),
            self::Video => __('enums.content_type.video'),
            self::Link => __('enums.content_type.link'),
        };
    }
}
