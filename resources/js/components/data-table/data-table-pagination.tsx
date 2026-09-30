import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

type Props<T> = {
    pagination: Paginated<T>;
};

export function DataTablePagination<T>({ pagination }: Props<T>) {
    const { from, to, total, prev_page_url, next_page_url } = pagination;

    return (
        <div className="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                Showing {from ?? 0}–{to ?? 0} of {total}
            </p>
            <div className="flex items-center gap-2">
                {prev_page_url ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={prev_page_url} preserveScroll>
                            <ChevronLeft />
                            Previous
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        <ChevronLeft />
                        Previous
                    </Button>
                )}
                {next_page_url ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={next_page_url} preserveScroll>
                            Next
                            <ChevronRight />
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        Next
                        <ChevronRight />
                    </Button>
                )}
            </div>
        </div>
    );
}
