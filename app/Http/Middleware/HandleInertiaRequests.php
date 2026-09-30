<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission as SpatiePermission;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'currency' => config('invoicing.currency'),
            'currencyLocale' => config('invoicing.currency_locale'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'permissions' => $this->permissions($request),
        ];
    }

    /**
     * Get the permission names the signed in user effectively holds.
     *
     * The navigation uses these to hide links the user cannot follow, so the
     * sidebar matches what the routes will actually allow.
     *
     * @return list<string>
     */
    protected function permissions(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        // A superuser holds everything, including permissions that were
        // created through the interface and are not in the enum.
        if ($user->isAdmin()) {
            return array_values(
                SpatiePermission::query()
                    ->where('guard_name', 'web')
                    ->get()
                    ->map(fn (SpatiePermission $permission): string => $permission->name)
                    ->all(),
            );
        }

        return array_values(array_map(
            fn (SpatiePermission $permission): string => $permission->name,
            $user->getAllPermissions()->all(),
        ));
    }
}
