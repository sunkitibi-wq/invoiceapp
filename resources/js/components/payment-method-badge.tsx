import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { PaymentMethodValue } from '@/types';

const methodStyles: Record<PaymentMethodValue, string> = {
    cash: 'border-transparent bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    bank_transfer:
        'border-transparent bg-blue-500/15 text-blue-700 dark:text-blue-400',
    card: 'border-transparent bg-violet-500/15 text-violet-700 dark:text-violet-400',
    cheque: 'border-transparent bg-amber-500/15 text-amber-700 dark:text-amber-400',
    other: 'border-transparent bg-neutral-500/20 text-muted-foreground',
};

export function PaymentMethodBadge({
    method,
    label,
    className,
}: {
    method: PaymentMethodValue;
    label: string;
    className?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn(methodStyles[method], className)}
        >
            {label}
        </Badge>
    );
}
