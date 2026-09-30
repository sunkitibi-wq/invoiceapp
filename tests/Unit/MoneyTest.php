<?php

namespace Tests\Unit;

use App\Casts\MoneyCast;
use App\Models\Service;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_created_from_cents_and_decimals(): void
    {
        $this->assertSame(1234, Money::of(1234)->cents);
        $this->assertSame(1234, Money::fromDecimal(12.34)->cents);
        $this->assertSame(0, Money::zero()->cents);
    }

    public function test_it_parses_user_input(): void
    {
        $this->assertSame(123456, Money::fromInput('1,234.56')->cents);
        $this->assertSame(150, Money::fromInput('1.50')->cents);
        $this->assertSame(150000, Money::fromInput('1,500')->cents);
        $this->assertSame(150, Money::fromInput('1,50')->cents);
        $this->assertSame(123456, Money::fromInput('1234,56')->cents);
        $this->assertSame(0, Money::fromInput('')->cents);
        $this->assertSame(0, Money::fromInput(null)->cents);
    }

    public function test_it_multiplies_and_rounds(): void
    {
        $this->assertSame(3750, Money::of(1250)->multipliedBy(3)->cents);
        $this->assertSame(1251, Money::of(1250)->multipliedBy(1.0005)->cents);
        $this->assertSame(250, Money::of(1000)->percentage(0.25)->cents);
    }

    public function test_it_adds_and_subtracts(): void
    {
        $this->assertSame(3000, Money::of(1000)->plus(Money::of(2000))->cents);
        $this->assertSame(500, Money::of(1000)->minus(500)->cents);
        $this->assertSame(3000, Money::of(1000)->plus(2000)->plus(0)->cents);
    }

    public function test_it_compares_and_limits(): void
    {
        $this->assertTrue(Money::zero()->isZero());
        $this->assertTrue(Money::of(-1)->isNegative());
        $this->assertSame(1000, Money::of(2000)->min(Money::of(1000))->cents);
        $this->assertSame(2000, Money::of(2000)->max(Money::of(1000))->cents);
        $this->assertSame(-1, Money::of(1000)->compare(Money::of(1001)));
    }

    public function test_it_formats_and_serialises(): void
    {
        config()->set('invoicing.currency', 'NGN');
        config()->set('invoicing.currency_locale', 'en_NG');

        $money = Money::of(123456);

        $this->assertSame('₦1,234.56', $money->format());
        $this->assertSame('₦1,234.56', (string) $money);
        $this->assertSame('₦1,234.56', $money->jsonSerialize());
        $this->assertSame('1234.56', $money->toInput());
        $this->assertSame(12.34, Money::of(1234)->toDecimal());
        $this->assertSame(
            ['cents' => 123456, 'formatted' => '₦1,234.56'],
            $money->toArray(),
        );
    }

    public function test_it_casts_to_and_from_database_values(): void
    {
        $cast = new MoneyCast;

        $model = new Service;

        $this->assertSame(2500, $cast->set($model, 'unit_price', Money::of(2500), []));
        $this->assertSame(2500, $cast->set($model, 'unit_price', 2500, []));
        $this->assertSame(2500, $cast->set($model, 'unit_price', '25.00', []));
        $this->assertSame(2500, $cast->set($model, 'unit_price', 25.0, []));
        $this->assertSame(0, $cast->set($model, 'unit_price', null, []));
        $this->assertSame(2500, $cast->get($model, 'unit_price', 2500, [])->cents);
    }

    public function test_it_is_usable_as_a_model_cast(): void
    {
        $service = Service::factory()->create(['unit_price' => '99.99']);

        $this->assertSame(9999, $service->fresh()->unit_price->cents);
    }
}
