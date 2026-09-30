<?php

namespace App\Support;

use App\Enums\Permission;

/**
 * Turns permission names into the grouped, labelled rows the screens render.
 *
 * Permissions are stored as "<resource>.<action>", so the name alone carries
 * enough structure to group and label them. Names defined in the Permission
 * enum keep their curated wording; anything created through the interface
 * falls back to a readable version of its own name.
 */
class PermissionCatalogue
{
    /**
     * Order a set of permission names for display.
     *
     * Names the application defines keep their declared order, because that is
     * the order an administrator reads them in. Anything created through the
     * interface follows, sorted alphabetically so the list stays stable.
     *
     * @param  list<string>  $names
     * @return list<string>
     */
    public static function ordered(array $names): array
    {
        $rank = [];

        foreach (Permission::cases() as $position => $permission) {
            $rank[$permission->value] = $position;
        }

        $sorted = $names;
        usort($sorted, function (string $a, string $b) use ($rank): int {
            $rankA = $rank[$a] ?? PHP_INT_MAX;
            $rankB = $rank[$b] ?? PHP_INT_MAX;

            return $rankA === $rankB ? strcmp($a, $b) : $rankA <=> $rankB;
        });

        return $sorted;
    }

    /**
     * Group permission names into the sections the matrix renders.
     *
     * @param  list<string>  $names
     * @return list<array{group: string, permissions: list<array{value: string, label: string}>}>
     */
    public static function grouped(array $names): array
    {
        $groups = [];

        foreach (self::ordered($names) as $name) {
            $groups[self::groupLabel($name)][] = [
                'value' => $name,
                'label' => self::label($name),
            ];
        }

        return array_map(
            fn (string $group, array $permissions): array => [
                'group' => $group,
                'permissions' => $permissions,
            ],
            array_keys($groups),
            $groups,
        );
    }

    /**
     * Get the readable section a permission belongs to.
     */
    public static function groupLabel(string $name): string
    {
        $defined = Permission::tryFrom($name);

        if ($defined !== null) {
            return $defined->group();
        }

        return ucfirst(str_replace('_', ' ', self::resource($name)));
    }

    /**
     * Get the readable description of what a permission allows.
     */
    public static function label(string $name): string
    {
        $defined = Permission::tryFrom($name);

        if ($defined !== null) {
            return self::sentence($defined->group(), $defined->actionLabel());
        }

        return self::sentence(self::groupLabel($name), ucfirst(self::action($name)));
    }

    /**
     * Determine whether the application checks for this permission in code.
     *
     * These names are referenced by the route middleware and the default
     * roles, so they cannot be renamed or removed without editing the code
     * that asks for them.
     */
    public static function isCodeDefined(string $name): bool
    {
        return Permission::tryFrom($name) !== null;
    }

    /**
     * Get the resource part of a permission name.
     */
    public static function resource(string $name): string
    {
        return explode('.', $name)[0];
    }

    /**
     * Get the action part of a permission name.
     */
    public static function action(string $name): string
    {
        return explode('.', $name)[1] ?? '';
    }

    /**
     * Combine a section and an action into a single readable phrase.
     */
    private static function sentence(string $group, string $action): string
    {
        $action = trim($action);
        $action = $action === '' ? 'Access' : $action;

        return $action === 'Access'
            ? $group
            : $action.' '.$group;
    }
}
