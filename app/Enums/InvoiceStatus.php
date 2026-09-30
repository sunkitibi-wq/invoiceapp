<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';

    /**
     * Get the human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Pending',
            self::PartiallyPaid => 'Partially paid',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Void => 'Void',
        };
    }

    /**
     * Determine whether the invoice may still be edited.
     *
     * An invoice can be revised for as long as money is still expected from
     * it. Paid and void invoices are terminal: their figures are settled or
     * cancelled, so changing the line items would contradict a payment or a
     * write off that has already happened.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::PartiallyPaid, self::Overdue], true);
    }

    /**
     * Determine whether the invoice is still expected to be paid.
     */
    public function isOutstanding(): bool
    {
        return in_array($this, [self::Sent, self::PartiallyPaid, self::Overdue], true);
    }

    /**
     * Get the list of statuses.
     *
     * @return list<self>
     */
    public static function values(): array
    {
        return self::cases();
    }
}
