import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import {
    StrictMode,
    type ComponentType,
    type ReactElement,
    type ReactNode,
} from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

type PageComponent = ComponentType & {
    layout?: Record<string, unknown> | ((page: ReactElement) => ReactNode);
};
const pages = import.meta.glob<{ default: PageComponent }>('./pages/**/*.tsx');
const appName = import.meta.env.VITE_APP_NAME || 'Teaching Studio';
void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const load = pages[`./pages/${name}.tsx`];
        if (!load) throw new Error(`Unknown application page: ${name}`);
        const page = (await load()).default;
        const metadata = typeof page.layout === 'object' ? page.layout : {};
        if (name.startsWith('auth/')) {
            page.layout = (child) => (
                <AuthLayout {...metadata}>{child}</AuthLayout>
            );
        } else if (name.startsWith('settings/')) {
            page.layout = (child) => (
                <AppLayout {...metadata}>
                    <SettingsLayout>{child}</SettingsLayout>
                </AppLayout>
            );
        } else if (!name.startsWith('studio/') && name !== 'welcome') {
            page.layout = (child) => (
                <AppLayout {...metadata}>{child}</AppLayout>
            );
        }
        const layout =
            typeof page.layout === 'function'
                ? page.layout
                : (child: ReactElement) => child;
        page.layout = (child) => (
            <>
                {layout(child)}
                <Toaster />
            </>
        );
        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <StrictMode>
                <TooltipProvider delayDuration={0}>
                    <App {...props} />
                </TooltipProvider>
            </StrictMode>,
        );
    },
    progress: { color: '#2448D8' },
});
initializeTheme();
