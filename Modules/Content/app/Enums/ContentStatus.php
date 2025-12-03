<?php

namespace Modules\Content\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Scheduled = 'scheduled';
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
            self::Draft => __('enums.content_status.draft'),
            self::Submitted => __('enums.content_status.submitted'),
            self::InReview => __('enums.content_status.in_review'),
            self::Approved => __('enums.content_status.approved'),
            self::Rejected => __('enums.content_status.rejected'),
            self::Scheduled => __('enums.content_status.scheduled'),
            self::Published => __('enums.content_status.published'),
            self::Archived => __('enums.content_status.archived'),
        };
    }
}
