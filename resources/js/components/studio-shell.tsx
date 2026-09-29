import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
export default function StudioShell({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    const { organization, auth } = usePage<{
        organization: string;
        auth: { user: { role: string } };
    }>().props;
    return (
        <>
            <Head title={title}>
                <link rel="stylesheet" href="/studio.css" />
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <a className="skip" href="#main">
                Skip to content
            </a>
            <div className="studio-layout">
                <header className="studio-nav">
                    <Link className="wordmark" href="/dashboard">
                        {organization} <span className="brand-dot" />
                    </Link>
                    <nav aria-label="Student navigation">
                        <Link href="/dashboard">My learning</Link>
                        <a href="/courses">Courses</a>
                        <Link href="/settings/profile">Account</Link>
                        {auth.user.role === 'admin' && (
                            <Link href="/admin/courses">Administration</Link>
                        )}
                        <button
                            className="secondary"
                            onClick={() => router.post('/logout')}
                        >
                            Sign out
                        </button>
                    </nav>
                </header>
                <main id="main">{children}</main>
            </div>
        </>
    );
}
