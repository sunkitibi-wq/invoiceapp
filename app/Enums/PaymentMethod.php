<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case Cheque = 'cheque';
    case Other = 'other';

    /**
     * Get the human readable label for the method.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank transfer',
            self::Card => 'Card',
            self::Cheque => 'Cheque',
            self::Other => 'Other',
        };
    }

    /**
     * Get the list of methods.
     *
     * @return list<self>
     */
    public static function values(): array
    {
        return self::cases();
    }
}
