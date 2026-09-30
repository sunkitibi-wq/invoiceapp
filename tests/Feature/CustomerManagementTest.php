<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_customer(): void
    {
        $this->actingAs($this->admin())
            ->post(route('customers.store'), [
                'name' => 'Ada Lovelace',
                'company' => 'Analytical Engines',
                'email' => 'ada@example.com',
                'phone' => '555-0100',
                'address_line_1' => '1 Engine Way',
                'city' => 'London',
                'country' => 'GB',
            ])
            ->assertSessionHasNoErrors();

        $customer = Customer::sole();

        $this->assertSame('Ada Lovelace', $customer->name);
        $this->assertSame('Analytical Engines', $customer->company);
        $this->assertTrue($customer->is_active);
    }

    public function test_it_requires_a_name_and_a_valid_email(): void
    {
        $this->actingAs($this->admin())
            ->post(route('customers.store'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_it_rejects_a_duplicate_email(): void
    {
        Customer::factory()->create(['email' => 'ada@example.com']);

        $this->actingAs($this->admin())
            ->post(route('customers.store'), [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_it_updates_a_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin())
            ->put(route('customers.update', $customer), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame('New Name', $customer->name);
        $this->assertFalse($customer->is_active);
    }

    public function test_it_archives_a_customer_that_still_has_invoices(): void
    {
        $customer = Customer::factory()->create();
        Invoice::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->admin())
            ->delete(route('customers.destroy', $customer))
            ->assertSessionHasNoErrors();

        $this->assertFalse($customer->refresh()->is_active);
        $this->assertDatabaseHas('invoices', ['customer_id' => $customer->id]);
    }

    public function test_it_deletes_a_customer_without_invoices(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_it_searches_customers(): void
    {
        Customer::factory()->create(['name' => 'Northwind Traders']);
        Customer::factory()->create(['name' => 'Initech']);

        $this->actingAs($this->admin())
            ->get(route('customers.index', ['search' => 'Northwind']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('customers.data', fn ($rows) => $rows->count() === 1)
                ->where('filters.search', 'Northwind'),
            );
    }

    public function test_it_hides_archived_customers_from_the_picker(): void
    {
        $active = Customer::factory()->create(['name' => 'Active Co']);
        Customer::factory()->create(['name' => 'Archived Co', 'is_active' => false]);

        $this->actingAs($this->admin())
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('customers', fn ($customers) => $customers->pluck('id')->all() === [$active->id]),
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
