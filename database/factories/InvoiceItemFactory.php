<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<InvoiceItem>
     */
    protected $model = InvoiceItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = Money::of(fake()->numberBetween(5_000, 50_000));
        $quantity = fake()->numberBetween(1, 20);

        return [
            'invoice_id' => Invoice::factory(),
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => null,
            'subtotal' => $unitPrice->multipliedBy($quantity),
            'tax_amount' => Money::zero(),
            'total' => $unitPrice->multipliedBy($quantity),
            'position' => 0,
        ];
    }
}
