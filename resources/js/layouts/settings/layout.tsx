import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
export default function SettingsLayout({ children }: PropsWithChildren) {
    const { url } = usePage();
    return (
        <section className="account-studio">
            <header className="page-heading">
                <p className="eyebrow">Make yourself at home</p>
                <h1>Your account.</h1>
                <p>Keep your details current and your learning secure.</p>
            </header>
            <div className="account-grid">
                <nav aria-label="Account settings" className="account-tabs">
                    {[
                        ['Profile', '/settings/profile'],
                        ['Password & security', '/settings/security'],
                        ['Appearance', '/settings/appearance'],
                    ].map(([label, href]) => (
                        <Link
                            key={href}
                            href={href}
                            aria-current={
                                url.startsWith(href) ? 'page' : undefined
                            }
                        >
                            {label}
                            <span>↗</span>
                        </Link>
                    ))}
                </nav>
                <div className="account-panel">{children}</div>
            </div>
        </section>
    );
}
