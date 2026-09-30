import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type Props = {
    route: string;
    defaultValue?: string;
    placeholder?: string;
    keep?: Record<string, string | number | null | undefined>;
    className?: string;
};

export function DataTableSearch({
    route,
    defaultValue = '',
    placeholder = 'Search…',
    keep = {},
    className,
}: Props) {
    const [value, setValue] = useState(defaultValue);

    useEffect(() => {
        if (value === defaultValue) {
            return;
        }

        const timeout = window.setTimeout(() => {
            router.get(
                route,
                { search: value === '' ? undefined : value, ...keep },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 300);

        return () => window.clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [value]);

    return (
        <div className={cn('relative', className)}>
            <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                value={value}
                onChange={(event) => setValue(event.target.value)}
                placeholder={placeholder}
                className="pl-8"
            />
        </div>
    );
}
