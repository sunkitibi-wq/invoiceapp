<?php

namespace Database\Factories;

use App\Models\Service;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Service>
     */
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Consulting',
                'Web development',
                'System administration',
                'Technical support',
                'Design',
            ]),
            'unit' => fake()->randomElement(['hour', 'day', 'month', 'project']),
            'description' => fake()->sentence(),
            'unit_price' => Money::of(fake()->numberBetween(5_000, 50_000)),
            'tax_rate' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the service is archived.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * Indicate that the service is billed with tax.
     */
    public function taxed(float $rate = 0.2): static
    {
        return $this->state(fn (): array => ['tax_rate' => $rate]);
    }
}
