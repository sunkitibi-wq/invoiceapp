<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('payments.index'))
            ->assertForbidden();
    }

    public function test_it_lists_payments_across_every_invoice_with_a_summary(): void
    {
        $this->seedPayments();

        $response = $this->actingAs($this->admin())->get(route('payments.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('payments/index')
                ->has('payments.data', 3)
                ->where('summary.count', 3)
                ->where('summary.received', Money::of(700_00)->format())
                ->has('paymentMethods', count(PaymentMethod::cases()))
                ->has('periods', 5)
            );
    }

    public function test_it_filters_payments_by_method(): void
    {
        $this->seedPayments();

        $this->actingAs($this->admin())
            ->get(route('payments.index', ['method' => PaymentMethod::Cash->value]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 1)
                ->where('payments.data.0.method', PaymentMethod::Cash->value)
            );
    }

    public function test_it_filters_payments_by_period(): void
    {
        $this->seedPayments();

        $this->actingAs($this->admin())
            ->get(route('payments.index', ['period' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('payments.data', 1));

        $this->actingAs($this->admin())
            ->get(route('payments.index', ['period' => 'year']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('payments.data', 3));
    }

    public function test_it_searches_payments_by_invoice_number_and_customer(): void
    {
        $this->seedPayments();

        $this->actingAs($this->admin())
            ->get(route('payments.index', ['search' => 'INV-1001']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('payments.data', 2));

        $this->actingAs($this->admin())
            ->get(route('payments.index', ['search' => 'Globex']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 2)
                ->where('payments.data.0.invoice.number', 'INV-1001')
            );
    }

    public function test_it_sorts_payments_by_amount(): void
    {
        $this->seedPayments();

        $this->actingAs($this->admin())
            ->get(route('payments.index', ['sort' => 'amount', 'direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('payments.data.0.amount.cents', 100_00)
                ->where('payments.data.2.amount.cents', 500_00)
            );
    }

    public function test_the_invoice_picker_only_offers_invoices_with_a_balance(): void
    {
        $outstanding = Invoice::factory()->awaitingPayment()->create();
        $settled = Invoice::factory()->paid()->create();

        $response = $this->actingAs($this->admin())
            ->getJson(route('payments.invoices'));

        $response->assertOk()
            ->assertJsonPath('invoices.0.id', $outstanding->id)
            ->assertJsonMissing(['id' => $settled->id]);
    }

    public function test_the_invoice_picker_searches_by_number_and_customer(): void
    {
        $target = Invoice::factory()->awaitingPayment()->create([
            'number' => 'INV-4242',
        ]);
        Invoice::factory()->awaitingPayment()->create(['number' => 'INV-9191']);
        Invoice::factory()->paid()->create(['number' => 'INV-5150']);

        $this->actingAs($this->admin())
            ->getJson(route('payments.invoices', ['search' => 'INV-4242']))
            ->assertOk()
            ->assertJsonCount(1, 'invoices')
            ->assertJsonPath('invoices.0.id', $target->id);

        $this->actingAs($this->admin())
            ->getJson(route('payments.invoices', ['search' => $target->customer->name]))
            ->assertOk()
            ->assertJsonPath('invoices.0.id', $target->id);
    }

    public function test_the_picker_requires_admin_access(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('payments.invoices'))
            ->assertForbidden();
    }

    /**
     * Record three payments across two invoices in different methods and periods.
     */
    private function seedPayments(): void
    {
        $globex = Customer::factory()->create([
            'name' => 'Globex',
            'company' => 'Globex Ltd',
        ]);

        $first = Invoice::factory()->create([
            'number' => 'INV-1001',
            'customer_id' => $globex->id,
        ]);
        $second = Invoice::factory()->create(['number' => 'INV-1002']);

        $first->payments()->create([
            'amount' => Money::of(500_00),
            'method' => PaymentMethod::Card,
            'paid_at' => now()->subDays(3),
            'reference' => 'CARD-1',
        ]);

        $second->payments()->create([
            'amount' => Money::of(100_00),
            'method' => PaymentMethod::Cash,
            'paid_at' => now(),
        ]);

        $first->payments()->create([
            'amount' => Money::of(100_00),
            'method' => PaymentMethod::Cheque,
            'paid_at' => now()->subMonths(8),
        ]);
    }

    /**
     * Create an admin user.
     */
    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
