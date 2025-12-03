<?php

namespace Modules\Assessments\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';
    case EWallet = 'e_wallet';
    case Cash = 'cash';

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
            self::BankTransfer => __('enums.payment_method.bank_transfer'),
            self::CreditCard => __('enums.payment_method.credit_card'),
            self::EWallet => __('enums.payment_method.e_wallet'),
            self::Cash => __('enums.payment_method.cash'),
        };
    }
}
