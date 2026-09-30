<?php

namespace App\Concerns;

use Illuminate\Support\Arr;

/**
 * Accepts tax rates as the percentages people type, storing them as fractions.
 */
trait NormalisesTaxRates
{
    /**
     * Convert the submitted percentages into the fractions models store.
     */
    protected function normaliseTaxRates(?string $itemsKey = null): void
    {
        $values = [
            'tax_rate' => $this->percentageToRate($this->input('tax_rate_percent')) ?? 0.0,
        ];

        if ($itemsKey !== null && is_array($items = $this->input($itemsKey))) {
            $values[$itemsKey] = array_map(function (mixed $item): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                $item['tax_rate'] = $this->percentageToRate(
                    Arr::get($item, 'tax_rate_percent', Arr::get($item, 'tax_rate'))
                );

                unset($item['tax_rate_percent']);

                return $item;
            }, $items);
        }

        $this->merge($values);
    }

    /**
     * Convert a percentage such as 20 into the stored rate 0.2, keeping an
     * omitted rate as null so line items inherit the invoice's rate.
     */
    private function percentageToRate(mixed $value): ?float
    {
        return $value === null || $value === ''
            ? null
            : round(((float) $value) / 100, 4);
    }
}
