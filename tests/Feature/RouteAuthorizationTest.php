<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The routes behind the invoicing permissions.
     *
     * @return list<array{0: string, 1: array<string, mixed>}>
     */
    public static function protectedRoutes(): array
    {
        return [
            ['dashboard', []],
            ['invoices.index', []],
            ['invoices.create', []],
            ['recurring-invoices.index', []],
            ['recurring-invoices.create', []],
            ['customers.index', []],
            ['customers.create', []],
            ['services.index', []],
            ['services.create', []],
            ['users.index', []],
            ['users.create', []],
            ['roles.index', []],
            ['roles.create', []],
            ['permissions.index', []],
            ['permissions.create', []],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_guests_are_redirected_to_login(string $route, array $parameters): void
    {
        $this->get(route($route, $parameters))->assertRedirect(route('login'));
    }

    #[DataProvider('protectedRoutes')]
    public function test_users_without_any_permission_are_forbidden(string $route, array $parameters): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route($route, $parameters))
            ->assertForbidden();
    }

    #[DataProvider('protectedRoutes')]
    public function test_superusers_can_manage_everything(string $route, array $parameters): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route($route, $parameters))
            ->assertOk();
    }

    public function test_a_role_grants_access_without_being_a_superuser(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Accountant');

        $this->assertFalse($user->isAdmin());

        $this->actingAs($user)->get(route('invoices.index'))->assertOk();
        $this->actingAs($user)->get(route('payments.index'))->assertOk();

        // Accountant deliberately excludes the access control resources.
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($user)->get(route('permissions.index'))->assertForbidden();
    }

    public function test_a_viewer_is_limited_to_reading(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($user)->get(route('invoices.index'))->assertOk();
        $this->actingAs($user)->get(route('invoices.create'))->assertForbidden();
    }

    public function test_a_role_is_revoked_immediately(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($user)->get(route('invoices.index'))->assertOk();

        $user->syncRoles([]);

        $this->actingAs($user)->get(route('invoices.index'))->assertForbidden();
    }

    public function test_unverified_users_must_verify_their_email(): void
    {
        $user = User::factory()->unverified()->admin()->create();

        $this->actingAs($user)->get(route('invoices.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_settings_stay_available_to_users_without_permissions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_only_superusers_carry_the_admin_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->is_admin);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->is_admin);
    }

    public function test_a_user_cannot_reach_a_record_by_id_without_permission(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('customers.edit', $customer))
            ->assertForbidden();
    }
}
