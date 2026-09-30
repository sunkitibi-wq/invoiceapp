<?php

namespace Tests\Unit;

use App\Enums\DiscountType;
use App\Support\InvoiceTotals;
use App\Support\Money;
use Tests\TestCase;

class InvoiceTotalsTest extends TestCase
{
    public function test_it_totals_a_single_line(): void
    {
        $totals = InvoiceTotals::calculate([
            ['quantity' => 2, 'unit_price' => Money::of(10_000), 'tax_rate' => null],
        ]);

        $this->assertSame(20_000, $totals->subtotal->cents);
        $this->assertSame(0, $totals->discount->cents);
        $this->assertSame(0, $totals->tax->cents);
        $this->assertSame(20_000, $totals->total->cents);
        $this->assertSame(20_000, $totals->lines[0]->subtotal->cents);
    }

    public function test_it_applies_the_invoice_tax_rate_to_every_line(): void
    {
        $totals = InvoiceTotals::calculate([
            ['quantity' => 1, 'unit_price' => Money::of(10_000)],
            ['quantity' => 3, 'unit_price' => Money::of(5_000)],
        ], discountType: null, defaultTaxRate: 0.1);

        $this->assertSame(25_000, $totals->subtotal->cents);
        $this->assertSame(2_500, $totals->tax->cents);
        $this->assertSame(27_500, $totals->total->cents);
    }

    public function test_a_line_tax_rate_overrides_the_invoice_rate(): void
    {
        $totals = InvoiceTotals::calculate([
            ['quantity' => 1, 'unit_price' => Money::of(10_000)],
            ['quantity' => 1, 'unit_price' => Money::of(10_000), 'tax_rate' => 0],
        ], defaultTaxRate: 0.1);

        $this->assertSame(1_000, $totals->tax->cents);
        $this->assertSame(21_000, $totals->total->cents);
    }

    public function test_a_percentage_discount_reduces_the_taxable_amount(): void
    {
        $totals = InvoiceTotals::calculate([
            ['quantity' => 1, 'unit_price' => Money::of(10_000)],
        ], DiscountType::Percent, 10, 0.1);

        $this->assertSame(10_000, $totals->subtotal->cents);
        $this->assertSame(1_000, $totals->discount->cents);
        $this->assertSame(900, $totals->tax->cents);
        $this->assertSame(9_900, $totals->total->cents);
    }

    public function test_a_fixed_discount_reduces_the_taxable_amount(): void
    {
        $totals = InvoiceTotals::calculate([
            ['quantity' => 1, 'unit_price' => Money::of(10_000)],
        ], DiscountType::Fixed, 25.5, 0.2);

        $this->assertSame(2_550, $totals->discount->cents);
        $this->assertSame(1_490, $totals->tax->cents);
        $this->assertSame(8_940, $totals->total->cents);
    }

    public function test_a_discount_never_exceeds_the_subtotal(): void
    {
        $totals = InvoiceTotals::calculate([
            ['quantity' => 1, 'unit_price' => Money::of(1_000)],
        ], DiscountType::Fixed, 500);

        $this->assertSame(1_000, $totals->discount->cents);
        $this->assertSame(0, $totals->total->cents);
    }

    public function test_line_taxes_always_sum_to_the_invoice_tax(): void
    {
        $totals = InvoiceTotals::calculate([
            ['quantity' => 1, 'unit_price' => Money::of(3_333)],
            ['quantity' => 1, 'unit_price' => Money::of(3_333), 'tax_rate' => 0.075],
            ['quantity' => 1, 'unit_price' => Money::of(3_333), 'tax_rate' => 0.2],
        ], DiscountType::Percent, 7.5, 0.05);

        $lineTax = array_sum(array_map(
            fn ($line): int => $line->tax->cents,
            $totals->lines,
        ));

        $this->assertSame($lineTax, $totals->tax->cents);
    }

    public function test_a_zero_discount_value_is_ignored(): void
    {
        $totals = InvoiceTotals::calculate([
            ['quantity' => 1, 'unit_price' => Money::of(1_000)],
        ], DiscountType::Percent, 0);

        $this->assertSame(0, $totals->discount->cents);
        $this->assertSame(1_000, $totals->total->cents);
    }
}
