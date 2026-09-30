import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

/**
 * PHP and ICU spell locales with underscores (en_NG) while Intl in the browser
 * requires the BCP 47 hyphenated form (en-NG).
 */
function toBcp47(locale: string): string {
    return locale.replace(/_/g, '-');
}

function createFormatter(locale: string, currency: string): Intl.NumberFormat {
    const options: Intl.NumberFormatOptions = { style: 'currency', currency };

    try {
        return new Intl.NumberFormat(toBcp47(locale), options);
    } catch {
        return new Intl.NumberFormat('en', options);
    }
}

/**
 * Format an amount held in minor units (e.g. kobo) using the currency and
 * locale shared by the server, so live client-side totals match the values
 * rendered by App\Support\Money.
 */
export function useMoneyFormatter(): (cents: number) => string {
    const { currency, currencyLocale } = usePage().props;

    return useMemo(() => {
        const formatter = createFormatter(currencyLocale, currency);

        return (cents: number) => formatter.format(cents / 100);
    }, [currency, currencyLocale]);
}
