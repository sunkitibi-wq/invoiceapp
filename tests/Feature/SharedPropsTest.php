<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as PermissionModel;
use Tests\TestCase;

class SharedPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shares_the_configured_currency_with_the_client(): void
    {
        config()->set('invoicing.currency', 'NGN');
        config()->set('invoicing.currency_locale', 'en_NG');

        $this->get(route('login'))
            ->assertInertia(fn ($page) => $page
                ->where('currency', 'NGN')
                ->where('currencyLocale', 'en_NG')
            );
    }

    public function test_it_shares_the_application_name(): void
    {
        config()->set('app.name', 'Toma');

        $this->get(route('login'))
            ->assertInertia(fn ($page) => $page
                ->where('name', 'Toma')
            );
    }

    public function test_a_guest_receives_no_permissions(): void
    {
        $this->get(route('login'))
            ->assertInertia(fn ($page) => $page
                ->where('permissions', [])
            );
    }

    public function test_a_user_without_roles_receives_no_permissions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('permissions', [])
            );
    }

    public function test_it_shares_the_permissions_granted_by_a_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $expected = array_values(array_map(
            fn (Permission $permission): string => $permission->value,
            array_filter(
                Permission::cases(),
                fn (Permission $permission): bool => $permission->action() === 'view'
                    && ! in_array($permission->resource(), ['users', 'roles', 'permissions'], true),
            ),
        ));

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('permissions', $expected)
            );
    }

    public function test_a_superuser_receives_every_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('permissions', array_column(Permission::cases(), 'value'))
            );
    }

    public function test_a_superuser_also_receives_permissions_created_in_the_interface(): void
    {
        $this->seed(RolePermissionSeeder::class);

        PermissionModel::create(['name' => 'reports.export', 'guard_name' => 'web']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('permissions', fn ($permissions): bool => collect($permissions)->contains('reports.export'))
            );
    }

    public function test_a_custom_permission_is_only_shared_once_granted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = PermissionModel::create(['name' => 'reports.export', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('permissions', fn ($permissions): bool => ! collect($permissions)->contains('reports.export'))
            );

        $user->givePermissionTo($permission);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('permissions', fn ($permissions): bool => collect($permissions)->contains('reports.export'))
            );
    }
}
