<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('roles.index'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_it_lists_every_role_with_its_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('roles/index')
                ->has('roles', 3)
                ->where('roles.0.name', 'Accountant')
                ->where('roles.0.users_count', 1)
                ->has('permissionGroups', count(Permission::grouped()))
            );
    }

    public function test_it_creates_a_role_with_the_selected_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('roles.store'), [
                'name' => 'Bookkeeper',
                'permissions' => [
                    Permission::ViewInvoices->value,
                    Permission::RecordPayments->value,
                ],
            ])
            ->assertRedirect(route('roles.index'));

        $role = Role::query()->where('name', 'Bookkeeper')->sole();

        $this->assertEqualsCanonicalizing(
            [
                Permission::ViewInvoices->value,
                Permission::RecordPayments->value,
            ],
            $role->permissions->pluck('name')->all(),
        );
    }

    public function test_a_role_can_be_created_without_any_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('roles.store'), ['name' => 'Intern'])
            ->assertRedirect(route('roles.index'));

        $this->assertSame(0, Role::query()->where('name', 'Intern')->sole()->permissions()->count());
    }

    public function test_it_rejects_permissions_outside_the_catalogue(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('roles.store'), [
                'name' => 'Sneaky',
                'permissions' => ['invoices.launch_missiles'],
            ])
            ->assertSessionHasErrors('permissions.0');

        $this->assertNull(Role::query()->where('name', 'Sneaky')->first());
    }

    public function test_role_names_must_be_unique(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('roles.store'), ['name' => 'Accountant'])
            ->assertSessionHasErrors('name');
    }

    public function test_it_replaces_the_permissions_of_a_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::query()->where('name', 'Viewer')->sole();

        $this->actingAs($this->superuser())
            ->put(route('roles.update', $role), [
                'name' => 'Read only',
                'permissions' => [Permission::ViewInvoices->value],
            ])
            ->assertRedirect(route('roles.index'));

        $this->assertSame('Read only', $role->refresh()->name);
        $this->assertSame([Permission::ViewInvoices->value], $role->permissions->pluck('name')->all());
    }

    public function test_a_role_still_assigned_to_a_user_cannot_be_deleted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::query()->where('name', 'Accountant')->sole();

        $this->actingAs($this->superuser())
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'));

        $this->assertNotNull($role->fresh());
    }

    public function test_it_deletes_an_unused_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::query()->where('name', 'Viewer')->sole();

        $this->actingAs($this->superuser())
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'));

        $this->assertNull($role->fresh());
    }

    public function test_granting_a_role_takes_effect_on_the_next_request(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();

        $this->assertFalse($user->can(Permission::ViewInvoices));

        $user->assignRole('Viewer');

        $this->actingAs($user)
            ->get(route('invoices.index'))
            ->assertOk();
    }

    /**
     * Create a superuser.
     */
    private function superuser(): User
    {
        return User::factory()->admin()->create();
    }
}
