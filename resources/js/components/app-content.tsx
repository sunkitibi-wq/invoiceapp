import type { ComponentProps } from 'react';
import { SidebarInset } from '@/components/ui/sidebar';

export function AppContent({ children, ...props }: ComponentProps<'main'>) {
    return <SidebarInset {...props}>{children}</SidebarInset>;
}
