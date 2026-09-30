<?php

namespace App\Http\Requests;

use App\Concerns\NormalisesTaxRates;
use App\Concerns\ValidatesInvoiceLines;
use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    use NormalisesTaxRates, ValidatesInvoiceLines;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice === null
            || ($invoice instanceof Invoice && $invoice->isEditable());
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
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
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
            'status' => [Rule::enum(InvoiceStatus::class)],
            'items' => ['required', 'array', 'min:1'],
            ...$this->lineItemRules('items'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => __('An invoice needs at least one line item.'),
            'items.min' => __('An invoice needs at least one line item.'),
        ];
    }
}
