import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { InvoiceStatus } from '@/types';

const statusStyles: Record<InvoiceStatus, string> = {
    draft: 'border-transparent bg-neutral-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200',
    sent: 'border-transparent bg-blue-600 text-white',
    partially_paid: 'border-transparent bg-amber-500 text-white',
    paid: 'border-transparent bg-emerald-600 text-white',
    overdue: 'border-transparent bg-red-600 text-white',
    void: 'border-transparent bg-neutral-500/20 text-muted-foreground',
};

export function InvoiceStatusBadge({
    status,
    label,
    className,
}: {
    status: InvoiceStatus;
    label: string;
    className?: string;
}) {
    return (
        <Badge className={cn(statusStyles[status], className)}>{label}</Badge>
    );
}
