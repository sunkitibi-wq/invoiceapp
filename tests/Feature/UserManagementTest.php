<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_permission_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }

    public function test_a_viewer_cannot_manage_users_or_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($user)
            ->post(route('users.store'), ['name' => 'X', 'email' => 'x@example.com', 'password' => 'password123'])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('roles.store'), ['name' => 'X'])
            ->assertForbidden();
    }

    public function test_it_lists_users_with_their_normalized_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = User::query()->where('email', 'accountant@example.com')->sole();

        $this->actingAs($this->superuser())
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('users/index')
                ->has('users.data', 2)
                ->has('users.data.0.roles')
                ->where('roles.0.label', 'Accountant')
            )
            ->assertSee('accountant@example.com');
    }

    public function test_it_creates_a_user_and_assigns_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountantRoleId = $this->roleId('Accountant');

        $this->actingAs($this->superuser())
            ->post(route('users.store'), [
                'name' => 'Grace',
                'email' => 'grace@example.com',
                'password' => 'secret-password-123',
                'password_confirmation' => 'secret-password-123',
                'roles' => [$accountantRoleId],
            ])
            ->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'grace@example.com')->sole();

        $this->assertTrue(Hash::check('secret-password-123', $user->password));
        $this->assertTrue($user->hasRole('Accountant'));
    }

    public function test_a_user_can_be_created_without_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('users.store'), [
                'name' => 'Lonely',
                'email' => 'lonely@example.com',
                'password' => 'secret-password-123',
                'password_confirmation' => 'secret-password-123',
                'roles' => [],
            ])
            ->assertRedirect(route('users.index'));

        $this->assertCount(0, User::query()->where('email', 'lonely@example.com')->sole()->roles);
    }

    public function test_it_validates_user_input(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('users.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_it_rejects_roles_that_do_not_exist(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs($this->superuser())
            ->post(route('users.store'), [
                'name' => 'Ghost',
                'email' => 'ghost@example.com',
                'password' => 'secret-password-123',
                'password_confirmation' => 'secret-password-123',
                'roles' => [99999],
            ])
            ->assertSessionHasErrors('roles.0');
    }

    public function test_it_updates_a_user_and_syncs_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($this->superuser())
            ->put(route('users.update', $user), [
                'name' => 'Renamed',
                'email' => $user->email,
                'roles' => [$this->roleId('Accountant')],
            ])
            ->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame('Renamed', $user->name);
        $this->assertTrue($user->hasRole('Accountant'));
        $this->assertFalse($user->hasRole('Viewer'));
    }

    public function test_updating_a_user_with_a_blank_password_keeps_the_existing_one(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $originalHash = $user->password;

        $this->actingAs($this->superuser())
            ->put(route('users.update', $user), [
                'name' => 'Same Password',
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'roles' => [],
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame($originalHash, $user->refresh()->password);
    }

    public function test_it_deletes_a_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($this->superuser())
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertNull($user->fresh());
    }

    public function test_a_user_cannot_delete_their_own_account(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superuser = $this->superuser();

        $this->actingAs($superuser)
            ->delete(route('users.destroy', $superuser))
            ->assertRedirect(route('users.index'));

        $this->assertNotNull($superuser->fresh());
    }

    public function test_the_last_superuser_cannot_be_deleted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $actor = User::factory()->admin()->create();
        $otherSuperuser = User::factory()->admin()->create();

        // Delete one of two superusers is fine.
        $this->actingAs($actor)
            ->delete(route('users.destroy', $otherSuperuser))
            ->assertRedirect(route('users.index'));

        $this->assertNull($otherSuperuser->fresh());

        // Now actor is the only superuser; a non-superuser must not delete them.
        $nonAdmin = User::factory()->create();
        $nonAdmin->assignRole('Administrator');

        $this->actingAs($nonAdmin)
            ->delete(route('users.destroy', $actor))
            ->assertRedirect(route('users.index'));

        $this->assertNotNull($actor->fresh());
    }

    public function test_a_superuser_bypasses_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superuser = User::factory()->admin()->create();

        $this->assertTrue($superuser->can(Permission::ViewInvoices));
        $this->assertTrue($superuser->can(Permission::DeleteRoles));

        $this->actingAs($superuser)->get(route('roles.index'))->assertOk();
    }

    public function test_the_make_admin_command_promotes_and_reverts(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $this->assertFalse($user->isAdmin());

        $this->artisan('user:make-admin', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->refresh()->isAdmin());

        $this->artisan('user:make-admin', ['email' => $user->email, '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->refresh()->isAdmin());
    }

    /**
     * Get a role's id by name.
     */
    private function roleId(string $name): int
    {
        return (int) Role::query()->where('name', $name)->sole()->id;
    }

    /**
     * Create a superuser.
     */
    private function superuser(): User
    {
        return User::factory()->admin()->create();
    }
}
