<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Payment>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount' => Money::of(fake()->numberBetween(5_000, 50_000)),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'paid_at' => now()->subDays(fake()->numberBetween(0, 10)),
            'reference' => fake()->optional()->bothify('REF-####'),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
