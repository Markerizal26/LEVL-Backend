<?php

namespace Modules\Learning\Enums;

enum AssignmentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

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
            self::Draft => __('enums.assignment_status.draft'),
            self::Published => __('enums.assignment_status.published'),
            self::Archived => __('enums.assignment_status.archived'),
        };
    }
}
