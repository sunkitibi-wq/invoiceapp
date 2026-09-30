<?php

namespace App\Support;

/**
 * The calculated amounts for a single invoice line.
 */
final readonly class InvoiceLineTotals
{
    public function __construct(
        public Money $subtotal,
        public Money $tax,
        public Money $total,
        public float $taxRate,
    ) {}
}
