import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * Standard page frame for every screen rendered inside the app shell.
 *
 * Owns the outer gutter so individual pages only describe their own internal
 * rhythm. Vertical spacing between page sections is left to the page.
 */
export function PageContainer({ className, ...props }: ComponentProps<'div'>) {
    return (
        <div
            className={cn('flex flex-1 flex-col p-4 md:p-6 lg:p-8', className)}
            {...props}
        />
    );
}
