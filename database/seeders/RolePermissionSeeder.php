<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The guard the application's roles and permissions belong to.
     */
    private const GUARD = 'web';

    /**
     * Seed the permission catalogue and the default roles.
     */
    public function run(): void
    {
        $this->syncPermissions();
        $this->forgetCache();

        foreach ($this->defaultRoles() as $name => $permissions) {
            Role::findOrCreate($name, self::GUARD)->syncPermissions($permissions);
        }

        $this->createSampleAccountant();

        $this->forgetCache();

        $this->command->info('Seeded permissions and roles: '.Role::query()->pluck('name')->implode(', '));
    }

    /**
     * Drop the permission registrar's cache.
     *
     * Spatie normally flushes this from a model observer, but model events are
     * suppressed while seeding, so anything that reads a permission by name
     * would otherwise work from a stale cache.
     */
    private function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Create a non superuser account so the role based access control can be
     * tried out without granting full access.
     */
    private function createSampleAccountant(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'accountant@example.com'],
            ['name' => 'Test Accountant', 'password' => 'password'],
        );

        $user->syncRoles(['Accountant']);
    }

    /**
     * Make sure every permission in the enum exists in the database.
     *
     * This only adds what is missing. Permissions created through the
     * interface are left alone, so seeding an existing install never strips
     * access somebody deliberately set up.
     */
    private function syncPermissions(): void
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, self::GUARD);
        }
    }

    /**
     * The roles created on a fresh installation.
     *
     * @return array<string, list<Permission>>
     */
    private function defaultRoles(): array
    {
        $viewable = array_values(array_filter(
            Permission::cases(),
            fn (Permission $permission): bool => $permission->action() === 'view',
        ));

        $accessControl = ['users', 'roles', 'permissions'];

        return [
            'Administrator' => Permission::cases(),
            'Accountant' => array_values(array_filter(
                Permission::cases(),
                fn (Permission $permission): bool => ! in_array(
                    $permission->resource(),
                    $accessControl,
                    true,
                ),
            )),
            'Viewer' => array_values(array_filter(
                $viewable,
                fn (Permission $permission): bool => ! in_array(
                    $permission->resource(),
                    $accessControl,
                    true,
                ),
            )),
        ];
    }
}
