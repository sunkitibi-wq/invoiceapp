<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\RoleRequest;
use App\Support\PermissionCatalogue;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(): Response
    {
        return Inertia::render('roles/index', [
            'roles' => Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions
                        ->pluck('name')
                        ->values()
                        ->all(),
                ]),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): Response
    {
        return Inertia::render('roles/create', [
            'role' => null,
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(RoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions', default: []));

        $this->forgetCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role created.'),
        ]);

        return to_route('roles.index');
    }

    /**
     * Show the form for editing the role.
     */
    public function edit(Role $role): Response
    {
        return Inertia::render('roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ],
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Update the role.
     */
    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $role->update(['name' => $request->validated('name')]);

        $role->syncPermissions($request->validated('permissions', default: []));

        $this->forgetCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role updated.'),
        ]);

        return to_route('roles.index');
    }

    /**
     * Remove the role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Deleting a role in use would silently strip access from its holders.
        if ($role->users()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This role is still assigned to a user, so it cannot be deleted.'),
            ]);

            return to_route('roles.index');
        }

        $role->delete();

        $this->forgetCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role deleted.'),
        ]);

        return to_route('roles.index');
    }

    /**
     * Build the permission matrix rows for the role form.
     *
     * @return list<array{group: string, permissions: list<array{value: string, label: string}>}>
     */
    private function permissionGroups(): array
    {
        return PermissionCatalogue::grouped($this->permissionNames());
    }

    /**
     * Get every permission the application knows about.
     *
     * @return list<string>
     */
    private function permissionNames(): array
    {
        return array_values(
            PermissionModel::query()
                ->where('guard_name', 'web')
                ->get()
                ->map(fn (PermissionModel $permission): string => $permission->name)
                ->all(),
        );
    }

    /**
     * Drop the permission cache so the new grants take effect immediately.
     */
    private function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
