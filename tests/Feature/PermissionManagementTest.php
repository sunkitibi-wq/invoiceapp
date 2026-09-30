<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('permissions.index'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_permission_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($user)->get(route('permissions.index'))->assertForbidden();
    }

    public function test_an_accountant_cannot_reach_the_module(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Accountant');

        $this->actingAs($user)->get(route('permissions.index'))->assertForbidden();
        $this->actingAs($user)->get(route('permissions.create'))->assertForbidden();
    }

    public function test_it_lists_the_catalogue_with_roles_and_status(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->get(route('permissions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('permissions/index')
                ->has('permissions', count(Permission::cases()))
                ->where('summary.missing', 0)
                ->where('summary.custom', 0)
                ->where('summary.total', count(Permission::cases()))
                ->where('permissions.0.name', 'customers.view')
                ->where('permissions.0.status', 'synced')
                ->where('permissions.0.is_code_defined', true)
            );
    }

    public function test_it_shows_which_roles_hold_a_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->get(route('permissions.index'))
            ->assertInertia(fn ($page) => $page
                ->where('permissions.0.name', 'customers.view')
                ->where('permissions.0.roles_count', 3)
                ->where('permissions.0.roles', fn ($roles) => collect($roles)
                    ->pluck('name')
                    ->all() === ['Accountant', 'Administrator', 'Viewer'])
            );
    }

    public function test_a_view_only_permission_is_not_offered_to_the_accountant(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->get(route('permissions.index'))
            ->assertInertia(fn ($page) => $page
                ->where('permissions', function ($permissions): bool {
                    $row = collect($permissions)->firstWhere('name', 'users.view');

                    return collect($row['roles'])->pluck('name')->all() === ['Administrator']
                        && $row['roles_count'] === 1;
                })
            );
    }

    public function test_it_searches_the_catalogue(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->get(route('permissions.index', ['search' => 'send']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('permissions', 1)
                ->where('permissions.0.name', 'invoices.send')
            );
    }

    public function test_it_reports_permissions_the_database_is_missing(): void
    {
        // Seeded, then one row removed to imitate a half migrated install.
        $this->seed(RolePermissionSeeder::class);
        PermissionModel::query()->where('name', 'invoices.void')->delete();

        $this->actingAs($this->superuser())
            ->get(route('permissions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('summary.missing', 1)
                ->where('permissions', function ($permissions): bool {
                    $row = collect($permissions)->firstWhere('name', 'invoices.void');

                    return $row['status'] === 'missing' && $row['id'] === null;
                })
            );
    }

    public function test_it_creates_a_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('permissions.store'), ['name' => 'reports.export'])
            ->assertRedirect(route('permissions.index'));

        $this->assertDatabaseHas('permissions', [
            'name' => 'reports.export',
            'guard_name' => 'web',
        ]);
    }

    public function test_a_new_permission_appears_as_custom(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('permissions.store'), ['name' => 'reports.export']);

        $this->actingAs($this->superuser())
            ->get(route('permissions.index'))
            ->assertInertia(fn ($page) => $page
                ->where('summary.custom', 1)
                ->where('permissions', function ($permissions): bool {
                    $row = collect($permissions)->firstWhere('name', 'reports.export');

                    return $row['status'] === 'custom'
                        && $row['is_code_defined'] === false
                        && $row['label'] === 'Export Reports';
                })
            );
    }

    public function test_it_rejects_a_malformed_permission_name(): void
    {
        $this->seed(RolePermissionSeeder::class);

        foreach (['invoices', 'invoices.send.now', 'Invoices.Send', 'invoices-send', ''] as $name) {
            $this->actingAs($this->superuser())
                ->post(route('permissions.store'), ['name' => $name])
                ->assertSessionHasErrors('name');
        }

        $this->assertDatabaseMissing('permissions', ['name' => 'invoices']);
    }

    public function test_it_rejects_a_duplicate_permission_name(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('permissions.store'), ['name' => 'invoices.send'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, PermissionModel::query()->where('name', 'invoices.send')->count());
    }

    public function test_it_renames_a_custom_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = $this->createCustom('reports.export');

        $this->actingAs($this->superuser())
            ->put(route('permissions.update', $permission), ['name' => 'reports.download'])
            ->assertRedirect(route('permissions.index'));

        $this->assertSame('reports.download', $permission->refresh()->name);
    }

    public function test_a_code_defined_permission_cannot_be_renamed(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = PermissionModel::query()->where('name', 'invoices.send')->sole();

        $this->actingAs($this->superuser())
            ->put(route('permissions.update', $permission), ['name' => 'invoices.email'])
            ->assertRedirect(route('permissions.index'));

        $this->assertSame('invoices.send', $permission->refresh()->name);
    }

    public function test_the_edit_page_refuses_a_code_defined_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = PermissionModel::query()->where('name', 'invoices.send')->sole();

        $this->actingAs($this->superuser())
            ->get(route('permissions.edit', $permission))
            ->assertRedirect(route('permissions.index'));
    }

    public function test_the_edit_page_opens_for_a_custom_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = $this->createCustom('reports.export');

        $this->actingAs($this->superuser())
            ->get(route('permissions.edit', $permission))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('permissions/edit')
                ->where('permission.name', 'reports.export')
            );
    }

    public function test_it_deletes_a_custom_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = $this->createCustom('reports.export');

        $this->actingAs($this->superuser())
            ->delete(route('permissions.destroy', $permission))
            ->assertRedirect(route('permissions.index'));

        $this->assertNull($permission->fresh());
    }

    public function test_deleting_a_permission_detaches_it_from_every_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = $this->createCustom('reports.export');
        $role = Role::query()->where('name', 'Viewer')->sole();
        $role->givePermissionTo($permission);

        $this->assertTrue($role->fresh()->hasDirectPermission('reports.export'));

        $this->actingAs($this->superuser())
            ->delete(route('permissions.destroy', $permission));

        // The pivot row goes, the role and its other permissions stay.
        $this->assertFalse($role->fresh()->permissions->contains('name', 'reports.export'));
        $this->assertTrue($role->fresh()->permissions->contains('name', 'customers.view'));
    }

    public function test_a_code_defined_permission_cannot_be_deleted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = PermissionModel::query()->where('name', 'invoices.send')->sole();

        $this->actingAs($this->superuser())
            ->delete(route('permissions.destroy', $permission))
            ->assertRedirect(route('permissions.index'));

        $this->assertNotNull($permission->fresh());
    }

    public function test_a_custom_permission_can_be_granted_to_a_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = $this->createCustom('reports.export');
        $role = Role::query()->where('name', 'Viewer')->sole();

        $this->actingAs($this->superuser())
            ->put(route('roles.update', $role), [
                'name' => $role->name,
                'permissions' => [$permission->name],
            ])
            ->assertRedirect(route('roles.index'));

        $this->assertTrue($role->fresh()->hasPermissionTo('reports.export'));
    }

    public function test_a_custom_permission_shows_up_in_the_role_matrix(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->createCustom('reports.export');

        $this->actingAs($this->superuser())
            ->get(route('roles.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('permissionGroups', function ($groups): bool {
                    $group = collect($groups)->firstWhere('group', 'Reports');

                    return collect($group['permissions'])
                        ->pluck('value')
                        ->all() === ['reports.export'];
                })
            );
    }

    public function test_seeding_does_not_remove_a_custom_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->createCustom('reports.export');

        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'reports.export']);
    }

    public function test_the_module_protects_itself_from_the_accountant_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = Role::query()->where('name', 'Accountant')->sole();
        $viewer = Role::query()->where('name', 'Viewer')->sole();

        foreach (Permission::cases() as $permission) {
            if ($permission->resource() === 'permissions') {
                $this->assertFalse(
                    $accountant->hasPermissionTo($permission),
                    "Accountant should not hold {$permission->value}.",
                );
                $this->assertFalse(
                    $viewer->hasPermissionTo($permission),
                    "Viewer should not hold {$permission->value}.",
                );
            }
        }
    }

    /**
     * Create a permission the application does not define in code.
     */
    private function createCustom(string $name): PermissionModel
    {
        return PermissionModel::create(['name' => $name, 'guard_name' => 'web']);
    }

    /**
     * Create a superuser.
     */
    private function superuser(): User
    {
        return User::factory()->admin()->create();
    }
}
