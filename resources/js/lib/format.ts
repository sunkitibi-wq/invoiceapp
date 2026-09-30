import type { Money } from '@/types';

export function formatDate(
    value: string | null | undefined,
    fallback = '—',
): string {
    if (!value) {
        return fallback;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(date);
}

export function formatMoney(value: Money): string {
    return value.formatted;
}

export function moneyToInput(value: Money): string {
    return (value.cents / 100).toFixed(2);
}

export function percentToInput(value: number | null | undefined): string {
    return value === null || value === undefined ? '' : String(value);
}

export function parseNumber(value: string): number | null {
    if (value === '') {
        return null;
    }

    const parsed = Number(value);

    return Number.isNaN(parsed) ? null : parsed;
}
