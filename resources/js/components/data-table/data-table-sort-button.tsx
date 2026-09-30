import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    sortKey: string;
    sort: string;
    direction: 'asc' | 'desc';
    route: string;
    keep?: Record<string, string | number | null | undefined>;
    defaultDirection?: 'asc' | 'desc';
    className?: string;
};

export function DataTableSortButton({
    label,
    sortKey,
    sort,
    direction,
    route,
    keep = {},
    defaultDirection = 'asc',
    className,
}: Props) {
    const isActive = sort === sortKey;
    const nextDirection = isActive
        ? direction === 'asc'
            ? 'desc'
            : 'asc'
        : defaultDirection;

    const handleClick = () => {
        router.get(
            route,
            {
                sort: sortKey,
                direction: nextDirection,
                ...keep,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const Icon = isActive
        ? direction === 'asc'
            ? ArrowUp
            : ArrowDown
        : ChevronsUpDown;

    return (
        <Button
            variant="ghost"
            size="sm"
            onClick={handleClick}
            className={cn(
                '-ml-3 h-8 gap-1 px-2 font-medium',
                isActive && 'text-foreground',
                className,
            )}
        >
            {label}
            <Icon className={cn('size-3.5', !isActive && 'opacity-40')} />
        </Button>
    );
}
