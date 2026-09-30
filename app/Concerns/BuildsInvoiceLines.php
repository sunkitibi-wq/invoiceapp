<?php

namespace App\Concerns;

use App\Enums\DiscountType;
use App\Models\Invoice;
use App\Support\InvoiceTotals;
use App\Support\Money;

/**
 * Shared line item handling for the create and update invoice actions.
 */
trait BuildsInvoiceLines
{
    /**
     * Replace the invoice's line items and store the recalculated amounts.
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function rebuildInvoiceLines(
        Invoice $invoice,
        array $items,
        ?DiscountType $discountType = null,
        float $discountValue = 0.0,
    ): void {
        $lines = $this->normaliseInvoiceLines($items, (float) $invoice->tax_rate);

        $totals = InvoiceTotals::calculate($lines, $discountType, $discountValue, (float) $invoice->tax_rate);

        $invoice->items()->delete();

        foreach ($lines as $position => $line) {
            $invoice->items()->create([
                'service_id' => $items[$position]['service_id'] ?? null,
                'description' => $items[$position]['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_rate' => $line['tax_rate'],
                'subtotal' => $totals->lines[$position]->subtotal,
                'tax_amount' => $totals->lines[$position]->tax,
                'total' => $totals->lines[$position]->total,
                'position' => $position,
            ]);
        }

        $invoice->update([
            'subtotal' => $totals->subtotal,
            'discount_amount' => $totals->discount,
            'tax_total' => $totals->tax,
            'total' => $totals->total,
        ]);
    }

    /**
     * Build the invoice attributes that are shared by create and update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function invoiceAttributes(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'],
            'recurring_invoice_id' => $data['recurring_invoice_id'] ?? null,
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'tax_rate' => (float) ($data['tax_rate'] ?? 0),
            'discount_type' => $this->resolveDiscountType($data),
            'discount_value' => $data['discount_value'] ?? null,
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
        ];
    }

    /**
     * Resolve the discount type, treating a missing value as no discount.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveDiscountType(array $data): ?DiscountType
    {
        return ($data['discount_value'] ?? null) === null
            ? null
            : DiscountType::from((string) $data['discount_type']);
    }

    /**
     * Convert raw line item input into the shape used for calculations.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array{quantity: float, unit_price: Money, tax_rate: float|null}>
     */
    private function normaliseInvoiceLines(array $items, float $defaultTaxRate): array
    {
        return array_map(function (array $item) use ($defaultTaxRate): array {
            $taxRate = $item['tax_rate'] ?? null;
            $rate = $taxRate === null || $taxRate === '' ? null : round((float) $taxRate, 4);

            return [
                'quantity' => round((float) ($item['quantity'] ?? 1), 3),
                'unit_price' => $this->toMoney($item['unit_price'] ?? null),
                'tax_rate' => $rate === null || $rate === round($defaultTaxRate, 4) ? null : $rate,
            ];
        }, $items);
    }

    /**
     * Resolve a line's unit price from either a value object or user input.
     */
    private function toMoney(mixed $value): Money
    {
        return match (true) {
            $value instanceof Money => $value,
            is_int($value) => Money::of($value),
            default => Money::fromInput((string) $value),
        };
    }
}
