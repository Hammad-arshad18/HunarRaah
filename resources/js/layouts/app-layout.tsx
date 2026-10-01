import type { ReactNode } from 'react';
import StudioShell from '@/components/studio-shell';
import type { BreadcrumbItem } from '@/types';
export default function AppLayout({
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}) {
    return <StudioShell title="Account">{children}</StudioShell>;
}
