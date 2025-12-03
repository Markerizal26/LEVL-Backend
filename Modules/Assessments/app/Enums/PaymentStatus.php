<?php

namespace Modules\Assessments\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

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
            self::Pending => __('enums.payment_status.pending'),
            self::Paid => __('enums.payment_status.paid'),
            self::Failed => __('enums.payment_status.failed'),
            self::Refunded => __('enums.payment_status.refunded'),
        };
    }
}
