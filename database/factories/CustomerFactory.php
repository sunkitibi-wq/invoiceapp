<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Customer>
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company' => fake()->boolean(70) ? fake()->company() : null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'tax_number' => fake()->optional(0.3)->bothify('??#####'),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional(0.5)->bothify('Apt ###'),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['AL', 'CA', 'NY', 'TX', 'WA']),
            'postal_code' => fake()->postcode(),
            'country' => 'US',
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the customer is archived.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * Indicate that the customer has no email address.
     */
    public function withoutEmail(): static
    {
        return $this->state(fn (): array => ['email' => null]);
    }

    /**
     * Indicate that the customer is a business.
     */
    public function company(string $name): static
    {
        return $this->state(fn (): array => [
            'company' => $name,
            'name' => Str::random(8),
        ]);
    }
}
