<?php

namespace Database\Factories;

use App\Enums\RecurrenceFrequency;
use App\Models\Customer;
use App\Models\RecurringInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringInvoice>
 */
class RecurringInvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<RecurringInvoice>
     */
    protected $model = RecurringInvoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'frequency' => fake()->randomElement(RecurrenceFrequency::cases()),
            'next_invoice_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'end_at' => null,
            'due_date_days' => (int) config('invoicing.default_payment_terms_days', 14),
            'tax_rate' => 0,
            'discount_type' => null,
            'discount_value' => null,
            'notes' => fake()->optional()->sentence(),
            'terms' => config('invoicing.default_terms'),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the schedule is overdue and ready to generate.
     */
    public function due(): static
    {
        return $this->state(fn (): array => [
            'next_invoice_at' => now()->subDay(),
        ]);
    }
}
