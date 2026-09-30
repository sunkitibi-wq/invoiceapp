<?php

namespace Database\Factories;

use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringInvoiceItem>
 */
class RecurringInvoiceItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<RecurringInvoiceItem>
     */
    protected $model = RecurringInvoiceItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recurring_invoice_id' => RecurringInvoice::factory(),
            'description' => fake()->sentence(3),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_price' => Money::of(fake()->numberBetween(5_000, 50_000)),
            'tax_rate' => null,
            'position' => 0,
        ];
    }
}
