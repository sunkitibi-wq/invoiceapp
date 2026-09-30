<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden_from_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertForbidden();
    }

    public function test_admin_users_can_visit_the_dashboard()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_it_totals_the_payments_received_this_month()
    {
        $this->actingAs(User::factory()->admin()->create());

        $invoice = Invoice::factory()->create();

        $invoice->payments()->create([
            'amount' => Money::of(25_000),
            'method' => PaymentMethod::Card,
            'paid_at' => now()->subDay(),
        ]);

        Invoice::factory()->create()->payments()->create([
            'amount' => Money::of(90_000),
            'method' => PaymentMethod::Cash,
            'paid_at' => now()->subMonths(4),
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.0.label', 'Received this month')
                ->where('stats.0.value', Money::of(25_000)->format())
            );
    }
}
