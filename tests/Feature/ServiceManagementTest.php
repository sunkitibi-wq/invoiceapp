<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_service(): void
    {
        $this->actingAs($this->admin())
            ->post(route('services.store'), [
                'name' => 'Consulting',
                'unit' => 'hour',
                'description' => 'Advisory work',
                'unit_price' => '150.00',
                'tax_rate_percent' => 20,
            ])
            ->assertSessionHasNoErrors();

        $service = Service::sole();

        $this->assertSame('Consulting', $service->name);
        $this->assertSame(15_000, $service->unit_price->cents);
        $this->assertSame(0.2, $service->tax_rate);
    }

    public function test_it_requires_a_name_and_a_price(): void
    {
        $this->actingAs($this->admin())
            ->post(route('services.store'), ['unit' => 'hour'])
            ->assertSessionHasErrors(['name', 'unit_price']);
    }

    public function test_it_rejects_a_negative_price(): void
    {
        $this->actingAs($this->admin())
            ->post(route('services.store'), [
                'name' => 'Consulting',
                'unit' => 'hour',
                'unit_price' => '-5.00',
            ])
            ->assertSessionHasErrors('unit_price');
    }

    public function test_it_updates_a_service(): void
    {
        $service = Service::factory()->create(['name' => 'Old']);

        $this->actingAs($this->admin())
            ->put(route('services.update', $service), [
                'name' => 'New',
                'unit' => 'month',
                'unit_price' => '1,250.00',
            ])
            ->assertSessionHasNoErrors();

        $service->refresh();

        $this->assertSame('New', $service->name);
        $this->assertSame(125_000, $service->unit_price->cents);
    }

    public function test_it_archives_a_service_used_on_an_invoice(): void
    {
        $service = Service::factory()->create();
        Invoice::factory()->forService($service)->create();

        $this->actingAs($this->admin())
            ->delete(route('services.destroy', $service))
            ->assertSessionHasNoErrors();

        $this->assertFalse($service->refresh()->is_active);
    }

    public function test_it_deletes_an_unused_service(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('services.destroy', $service))
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_it_offers_only_active_services_on_the_invoice_form(): void
    {
        $active = Service::factory()->create(['name' => 'Active']);
        Service::factory()->create(['name' => 'Archived', 'is_active' => false]);

        $this->actingAs($this->admin())
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('services', fn ($services) => $services->pluck('id')->all() === [$active->id]),
            );
    }

    /**
     * Create an admin user.
     */
    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
