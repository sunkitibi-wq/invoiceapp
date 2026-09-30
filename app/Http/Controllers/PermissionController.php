<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\PermissionRequest;
use App\Support\PermissionCatalogue;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    /**
     * Display the catalogue of permissions.
     */
    public function index(): Response
    {
        $rows = $this->catalogue();

        return Inertia::render('permissions/index', [
            'permissions' => $this->filter($rows),
            'summary' => $this->summarise($rows),
            'filters' => [
                'search' => request('search'),
            ],
        ]);
    }

    /**
     * Show the form for creating a permission.
     */
    public function create(): Response
    {
        return Inertia::render('permissions/create');
    }

    /**
     * Store a newly created permission.
     */
    public function store(PermissionRequest $request): RedirectResponse
    {
        PermissionModel::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $this->forgetCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Permission created.'),
        ]);

        return to_route('permissions.index');
    }

    /**
     * Show the form for editing a permission.
     */
    public function edit(PermissionModel $permission): Response|RedirectResponse
    {
        if ($reason = $this::blocksEdit($permission)) {
            return $this->refuse($reason, 'permissions.index');
        }

        return Inertia::render('permissions/edit', [
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => PermissionCatalogue::label($permission->name),
                'group' => PermissionCatalogue::groupLabel($permission->name),
                'is_code_defined' => PermissionCatalogue::isCodeDefined($permission->name),
            ],
        ]);
    }

    /**
     * Update the permission.
     */
    public function update(PermissionRequest $request, PermissionModel $permission): RedirectResponse
    {
        if ($reason = $this::blocksEdit($permission)) {
            return $this->refuse($reason, 'permissions.index');
        }

        $permission->update(['name' => $request->validated('name')]);

        $this->forgetCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Permission updated.'),
        ]);

        return to_route('permissions.index');
    }

    /**
     * Remove the permission.
     */
    public function destroy(PermissionModel $permission): RedirectResponse
    {
        if ($reason = $this::blocksEdit($permission)) {
            return $this->refuse($reason, 'permissions.index');
        }

        $permission->delete();

        $this->forgetCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Permission deleted.'),
        ]);

        return to_route('permissions.index');
    }

    /**
     * Explain why a permission cannot be renamed or removed, if it cannot.
     *
     * The application asks for these names in the route middleware and in the
     * default roles, and seeding puts them back. Renaming or deleting one here
     * would leave the code asking for a permission that no longer exists, so
     * it has to happen in the enum instead.
     */
    private static function blocksEdit(PermissionModel $permission): ?string
    {
        if (! PermissionCatalogue::isCodeDefined($permission->name)) {
            return null;
        }

        return __('This permission is used by the application itself, so it cannot be renamed or deleted.');
    }

    /**
     * Send the user back with an explanation instead of acting on the request.
     */
    private function refuse(string $reason, string $route): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $reason]);

        return to_route($route);
    }

    /**
     * Build the catalogue rows shown on the index.
     *
     * @return list<array<string, mixed>>
     */
    private function catalogue(): array
    {
        $stored = [];
        $rolesByPermission = [];

        $permissions = PermissionModel::query()
            ->where('guard_name', 'web')
            ->with('roles')
            ->get();

        foreach ($permissions as $permission) {
            $stored[$permission->name] = $permission->id;
            $rolesByPermission[$permission->name] = $permission
                ->roles
                ->sortBy('name')
                ->map(fn ($role): array => ['id' => $role->id, 'name' => $role->name])
                ->values()
                ->all();
        }

        $rows = array_map(function (string $name) use ($stored, $rolesByPermission): array {
            $roles = $rolesByPermission[$name] ?? [];

            return [
                'id' => $stored[$name],
                'name' => $name,
                'label' => PermissionCatalogue::label($name),
                'group' => PermissionCatalogue::groupLabel($name),
                'status' => PermissionCatalogue::isCodeDefined($name) ? 'synced' : 'custom',
                'is_code_defined' => PermissionCatalogue::isCodeDefined($name),
                'roles' => $roles,
                'roles_count' => count($roles),
            ];
        }, PermissionCatalogue::ordered(array_keys($stored)));

        // Anything the code checks for but the database has not caught up
        // with yet. Its route middleware will deny everyone until it is
        // seeded, so it is worth showing even though it cannot be edited.
        foreach (Permission::cases() as $permission) {
            if (array_key_exists($permission->value, $stored)) {
                continue;
            }

            $rows[] = [
                'id' => null,
                'name' => $permission->value,
                'label' => PermissionCatalogue::label($permission->value),
                'group' => $permission->group(),
                'status' => 'missing',
                'is_code_defined' => true,
                'roles' => [],
                'roles_count' => 0,
            ];
        }

        return $rows;
    }

    /**
     * Count the catalogue by sync state.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function summarise(array $rows): array
    {
        $count = fn (string $status): int => count(array_filter(
            $rows,
            fn (array $row): bool => $row['status'] === $status,
        ));

        return [
            'total' => count($rows),
            'synced' => $count('synced'),
            'custom' => $count('custom'),
            'missing' => $count('missing'),
        ];
    }

    /**
     * Narrow the catalogue to the search term.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filter(array $rows): array
    {
        $search = trim((string) request('search', ''));

        if ($search === '') {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($search): bool {
            $haystack = strtolower($row['name'].' '.$row['label'].' '.$row['group']);

            return str_contains($haystack, strtolower($search));
        }));
    }

    /**
     * Drop the permission cache so a change takes effect immediately.
     */
    private function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
