<?php

namespace App\Concerns;

use App\Rules\MoneyInput;
use Illuminate\Validation\Rule;

/**
 * Shared line item validation for invoice forms.
 */
trait ValidatesInvoiceLines
{
    /**
     * Get the validation rules for a set of line items.
     *
     * @return array<string, mixed>
     */
    private function lineItemRules(string $key): array
    {
        return [
            "{$key}.*.service_id" => ['nullable', Rule::exists('services', 'id')],
            "{$key}.*.description" => ['required', 'string', 'max:1000'],
            "{$key}.*.quantity" => ['required', 'numeric', 'gt:0', 'max:1000000'],
            "{$key}.*.unit_price" => ['required', new MoneyInput],
            "{$key}.*.tax_rate_percent" => ['nullable', 'numeric', 'min:0', 'max:100'],
            "{$key}.*.tax_rate" => ['nullable', 'numeric', 'min:0', 'max:1'],
        ];
    }
}
