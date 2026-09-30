<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithTables;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use InteractsWithTables;

    /**
     * Display a listing of the users.
     */
    public function index(): Response
    {
        $roles = $this->roleOptions();

        $users = User::query()
            ->when(request('search'), fn ($query, $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->tap(fn ($query) => $this->applySort($query, ['name', 'email', 'created_at'], 'name'))
            ->paginate($this->perPage())
            ->withQueryString();

        return Inertia::render('users/index', [
            'users' => $users->through(
                fn (User $user): array => [
                    ...$user->toArray(),
                    'roles' => $this->assignedRoles($user, $roles),
                ],
            ),
            'filters' => [
                'search' => request('search'),
            ],
            'roles' => $roles,
        ]);
    }

    /**
     * Reduce a user's roles to the same option shape the form uses.
     *
     * @param  list<array{value: int, label: string}>  $options
     * @return list<array{value: int, label: string}>
     */
    private function assignedRoles(User $user, array $options): array
    {
        $assigned = $user->roles->pluck('id')->all();

        return array_values(array_filter(
            $options,
            fn (array $option): bool => in_array($option['value'], $assigned, true),
        ));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('users/create', [
            'user' => null,
            'roles' => $this->roleOptions(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->except('roles'));

        $user->syncRoles($request->validated('roles', default: []));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User created.'),
        ]);

        return to_route('users.index');
    }

    /**
     * Show the form for editing the user.
     */
    public function edit(User $user): Response
    {
        $roles = $this->roleOptions();

        return Inertia::render('users/edit', [
            'user' => [
                ...$user->toArray(),
                'roles' => $this->assignedRoles($user->load('roles'), $roles),
            ],
            'roles' => $roles,
        ]);
    }

    /**
     * Update the user.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $attributes = $request->safe()->except('roles');

        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        $user->fill($attributes);

        $user->save();

        $user->syncRoles($request->validated('roles', default: []));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User updated.'),
        ]);

        return to_route('users.index');
    }

    /**
     * Remove the user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($reason = $this->blocksDeletion($request, $user)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $reason]);

            return to_route('users.index');
        }

        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User deleted.'),
        ]);

        return to_route('users.index');
    }

    /**
     * Explain why the user cannot be deleted, if they cannot.
     */
    private function blocksDeletion(Request $request, User $user): ?string
    {
        if ($user->is($request->user())) {
            return __('You cannot delete your own account.');
        }

        // Removing the final superuser would leave nobody able to manage roles.
        if ($user->isAdmin() && ! User::query()
            ->where('is_admin', true)
            ->whereKeyNot($user->getKey())
            ->exists()
        ) {
            return __('This is the last superuser, so it cannot be deleted.');
        }

        return null;
    }

    /**
     * Build the role options for the user form.
     *
     * @return list<array{value: int, label: string}>
     */
    private function roleOptions(): array
    {
        return array_values(array_map(
            fn (Role $role): array => [
                'value' => (int) $role->getKey(),
                'label' => $role->name,
            ],
            Role::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->all(),
        ));
    }
}
