<?php

namespace App\Support;

use App\Enums\DiscountType;

/**
 * The calculated amounts for an invoice, derived from its line items.
 */
final readonly class InvoiceTotals
{
    /**
     * @param  list<InvoiceLineTotals>  $lines
     */
    private function __construct(
        public array $lines,
        public Money $subtotal,
        public Money $discount,
        public Money $tax,
        public Money $total,
    ) {}

    /**
     * Calculate the amounts for the given line items.
     *
     * An invoice level discount is applied before tax, and each line's tax is
     * scaled by the share of the subtotal that remains taxable so that the sum
     * of the line taxes always matches the invoice tax total.
     *
     * @param  list<array{quantity: float, unit_price: Money, tax_rate?: float|null}>  $lines
     */
    public static function calculate(
        array $lines,
        ?DiscountType $discountType = null,
        float $discountValue = 0.0,
        float $defaultTaxRate = 0.0,
    ): self {
        $subtotal = Money::zero();

        foreach ($lines as $line) {
            $subtotal = $subtotal->plus($line['unit_price']->multipliedBy($line['quantity']));
        }

        $discount = self::calculateDiscount($subtotal, $discountType, $discountValue);
        $taxableSubtotal = $subtotal->minus($discount);
        $taxableRatio = $subtotal->isPositive() ? $taxableSubtotal->toDecimal() / $subtotal->toDecimal() : 0.0;

        $lineTotals = [];
        $tax = Money::zero();

        foreach ($lines as $line) {
            $taxRate = $line['tax_rate'] ?? $defaultTaxRate;
            $lineSubtotal = $line['unit_price']->multipliedBy($line['quantity']);
            $lineTax = Money::of((int) round($lineSubtotal->percentage($taxRate)->cents * $taxableRatio));

            $tax = $tax->plus($lineTax);

            $lineTotals[] = new InvoiceLineTotals(
                subtotal: $lineSubtotal,
                tax: $lineTax,
                total: $lineSubtotal->plus($lineTax),
                taxRate: $taxRate,
            );
        }

        return new self(
            lines: $lineTotals,
            subtotal: $subtotal,
            discount: $discount,
            tax: $tax,
            total: $taxableSubtotal->plus($tax),
        );
    }

    /**
     * Determine the discount amount, never more than the subtotal itself.
     */
    private static function calculateDiscount(Money $subtotal, ?DiscountType $discountType, float $discountValue): Money
    {
        if ($discountType === null || $discountValue <= 0) {
            return Money::zero();
        }

        $discount = $discountType === DiscountType::Percent
            ? $subtotal->percentage($discountValue / 100)
            : Money::fromDecimal($discountValue);

        return $discount->min($subtotal);
    }

    /**
     * Determine the subtotal that tax is charged on.
     */
    public function taxableSubtotal(): Money
    {
        return $this->subtotal->minus($this->discount);
    }
}
