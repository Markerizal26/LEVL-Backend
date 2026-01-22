<?php

declare(strict_types=1);

namespace Modules\Grading\Enums;

enum AppealStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Denied = 'denied';

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
            self::Pending => __('enums.appeal_status.pending'),
            self::Approved => __('enums.appeal_status.approved'),
            self::Denied => __('enums.appeal_status.denied'),
        };
    }

    /**
     * Check if the appeal is still pending.
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Check if the appeal has been decided.
     */
    public function isDecided(): bool
    {
        return $this !== self::Pending;
    }

    /**
     * Check if the appeal was approved.
     */
    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    /**
     * Check if the appeal was denied.
     */
    public function isDenied(): bool
    {
        return $this === self::Denied;
    }
}
