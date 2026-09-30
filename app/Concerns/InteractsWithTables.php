<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared listing behaviour for the data tables.
 */
trait InteractsWithTables
{
    /**
     * Get the number of records to display per page.
     */
    protected function perPage(): int
    {
        return min(max((int) request('per_page', 15), 5), 100);
    }

    /**
     * Apply the requested sort, falling back to the default column.
     *
     * @param  Builder<*>  $query
     * @param  list<string>  $sortable
     * @param  'asc'|'desc'  $defaultDirection
     */
    protected function applySort(Builder $query, array $sortable, string $default, string $defaultDirection = 'asc'): void
    {
        $sort = (string) request('sort', $default);
        $direction = request('direction') === 'desc' ? 'desc' : 'asc';

        $query->orderBy(
            in_array($sort, $sortable, true) ? $sort : $default,
            $sort === $default ? $defaultDirection : $direction,
        );
    }
}
