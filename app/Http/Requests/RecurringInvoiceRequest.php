<?php

namespace App\Http\Requests;

use App\Concerns\NormalisesTaxRates;
use App\Concerns\ValidatesInvoiceLines;
use App\Enums\DiscountType;
use App\Enums\RecurrenceFrequency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringInvoiceRequest extends FormRequest
{
    use NormalisesTaxRates, ValidatesInvoiceLines;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get data ready for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->normaliseTaxRates('items');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')],
            'frequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            'next_invoice_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:next_invoice_at'],
            'due_date_days' => ['required', 'integer', 'min:0', 'max:365'],
            'tax_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'discount_type' => [
                Rule::enum(DiscountType::class),
                Rule::requiredIf(fn (): bool => $this->filled('discount_value')),
            ],
            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn (): bool => $this->filled('discount_type')),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            ...$this->lineItemRules('items'),
        ];
    }
}
