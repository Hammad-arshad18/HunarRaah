import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import {
    BookOpen,
    CalendarDays,
    CreditCard,
    Award,
    Settings,
    Library,
    Users,
    History,
    Menu,
    X,
    ArrowUpRight,
    LogOut,
} from 'lucide-react';
import StudioMark from '@/components/studio-mark';
export default function StudioShell({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    const { organization, auth, flash, csrfToken } = usePage<{
        organization: string;
        csrfToken: string;
        auth: { user: { name: string; role: string } };
        flash: { operation_error?: string };
    }>().props;
    const { url } = usePage();
    const [open, setOpen] = useState(false);
    const [offline, setOffline] = useState(false);
    useEffect(() => {
        const update = () => setOffline(!navigator.onLine);
        update();
        window.addEventListener('online', update);
        window.addEventListener('offline', update);
        return () => {
            window.removeEventListener('online', update);
            window.removeEventListener('offline', update);
        };
    }, []);
    useEffect(() => {
        if (!open) return;
        const nav = document.getElementById('workspace-nav');
        const focusable = () =>
            Array.from(
                nav?.querySelectorAll<HTMLElement>(
                    'a[href], button:not(:disabled)',
                ) || [],
            ).filter((el) => el.offsetParent !== null);
        const focusMenu = () => focusable()[0]?.focus({ preventScroll: true });
        const focusFrame = requestAnimationFrame(focusMenu);
        nav?.addEventListener('transitionend', focusMenu, { once: true });
        const handleKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
                return;
            }
            if (event.key !== 'Tab') return;
            const items = focusable();
            if (event.shiftKey && document.activeElement === items[0]) {
                event.preventDefault();
                items.at(-1)?.focus();
            } else if (
                !event.shiftKey &&
                document.activeElement === items.at(-1)
            ) {
                event.preventDefault();
                items[0]?.focus();
            }
        };
        document.addEventListener('keydown', handleKey);
        return () => {
            cancelAnimationFrame(focusFrame);
            nav?.removeEventListener('transitionend', focusMenu);
            document.removeEventListener('keydown', handleKey);
            document.getElementById('navigation-toggle')?.focus();
        };
    }, [open]);
    const admin = url.startsWith('/admin');
    const items = admin
        ? ([
              ['Courses', '/admin/courses', Library],
              ['Students & records', '/admin/operations', Users],
              ['Activity log', '/admin/activity', History],
          ] as const)
        : ([
              ['My learning', '/dashboard', BookOpen],
              ['Live schedule', '/schedule', CalendarDays],
              ['Certificates', '/credentials', Award],
              ['Purchases', '/purchases', CreditCard],
              ['Account', '/settings/profile', Settings],
          ] as const);
    return (
        <>
            <Head title={title}>
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <a className="skip" href="#main">
                Skip to content
            </a>
            <div className="workspace">
                <header className="workspace-mobile" inert={open}>
                    <Link className="wordmark" href="/dashboard">
                        <StudioMark />
                        {organization}
                    </Link>
                    <button
                        className="icon-button"
                        id="navigation-toggle"
                        aria-label={
                            open ? 'Close navigation' : 'Open navigation'
                        }
                        aria-expanded={open}
                        aria-controls="workspace-nav"
                        onClick={() => setOpen(!open)}
                    >
                        {open ? <X /> : <Menu />}
                    </button>
                </header>
                {open && (
                    <button
                        className="nav-scrim"
                        aria-label="Close navigation"
                        onClick={() => setOpen(false)}
                    />
                )}
                <aside
                    id="workspace-nav"
                    className={`workspace-sidebar ${open ? 'is-open' : ''}`}
                    role={open ? 'dialog' : undefined}
                    aria-modal={open || undefined}
                    aria-label={open ? 'Navigation' : undefined}
                >
                    <button
                        className="icon-button nav-close"
                        aria-label="Close navigation"
                        onClick={() => setOpen(false)}
                    >
                        <X />
                    </button>
                    <Link className="wordmark" href="/dashboard">
                        <StudioMark />
                        <span>{organization}</span>
                    </Link>
                    <p className="nav-caption">
                        {admin ? 'The teaching desk' : 'Your learning space'}
                    </p>
                    <nav
                        aria-label={
                            admin ? 'Administration' : 'Student navigation'
                        }
                    >
                        {items.map(([label, href, Icon]) => (
                            <Link
                                key={href}
                                href={href}
                                onClick={() => setOpen(false)}
                                aria-current={
                                    (
                                        href === '/dashboard'
                                            ? url === href
                                            : url.startsWith(href)
                                    )
                                        ? 'page'
                                        : undefined
                                }
                            >
                                <Icon size={18} />
                                <span>{label}</span>
                            </Link>
                        ))}
                    </nav>
                    <a className="explore-link" href="/courses">
                        Explore courses <ArrowUpRight size={17} />
                    </a>
                    {auth.user.role === 'admin' && (
                        <a
                            className="explore-link"
                            href={admin ? '/dashboard' : '/admin'}
                        >
                            {admin ? 'Student view' : 'Administration'}{' '}
                            <ArrowUpRight size={17} />
                        </a>
                    )}
                    <div className="sidebar-bottom">
                        <div className="user-stamp">
                            <span>
                                {auth.user.name.slice(0, 1).toUpperCase()}
                            </span>
                            <div>
                                <strong>{auth.user.name}</strong>
                                <small>
                                    {admin
                                        ? 'Administrator'
                                        : 'Learning account'}
                                </small>
                            </div>
                        </div>
                        <form action="/logout" method="post">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <button type="submit"><LogOut size={16} />Sign out</button>
                        </form>
                        <a href="/support">Help & support ↗</a>
                    </div>
                </aside>
                <div className="workspace-body" inert={open}>
                    <header className="workspace-topline">
                        <span>
                            {admin ? 'ADMINISTRATION' : 'THE STUDIO'}{' '}
                            <span className="topline-dot">/</span> {title}
                        </span>
                        <a href="/courses">Course collection ↗</a>
                    </header>
                    <main id="main" tabIndex={-1}>
                        {offline && (
                            <p className="note" role="status">
                                You’re offline. Reconnect before saving changes
                                or opening a recording.
                            </p>
                        )}
                        {flash?.operation_error && (
                            <p className="operation-error" role="alert">
                                {flash.operation_error}
                            </p>
                        )}
                        {children}
                    </main>
                    <footer className="workspace-footer">
                        <span>{organization} · Learning, thoughtfully.</span>
                        <a href="/support">Support</a>
                    </footer>
                </div>
            </div>
        </>
    );
}
