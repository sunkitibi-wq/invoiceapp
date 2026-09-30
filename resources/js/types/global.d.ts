import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            currency: string;
            currencyLocale: string;
            sidebarOpen: boolean;
            permissions: string[];
            [key: string]: unknown;
        };
    }
}
