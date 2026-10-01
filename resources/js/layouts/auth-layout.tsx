import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import StudioMark from '@/components/studio-mark';
export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: ReactNode;
}) {
    const page = usePage<{ organization: string }>();
    const { organization } = page.props;
    const adminSetup = page.url.startsWith('/admin/setup');
    return (
        <div className="auth-studio">
            <a className="skip" href="#main">
                Skip to content
            </a>
            <aside className="auth-story">
                <a href="/" className="wordmark">
                    <StudioMark />
                    {organization}
                </a>
                <div className="auth-story-content">
                    <p className="eyebrow">
                        A place to practice. A path to progress.
                    </p>
                    <h2>
                        Your next
                        <br />
                        chapter starts
                        <br />
                        <em>right here.</em>
                    </h2>
                    <p>
                        Focused courses. Thoughtful lessons. The space to turn
                        curiosity into something you can do.
                    </p>
                    <ol className="auth-path">
                        <li>
                            <span>01</span>Find your starting point
                        </li>
                        <li>
                            <span>02</span>Learn one lesson at a time
                        </li>
                        <li>
                            <span>03</span>Make your progress tangible
                        </li>
                    </ol>
                </div>
                <div className="auth-story-footer">
                    <span>Learn with intention.</span>
                    <a href="/courses">Explore the collection ↗</a>
                </div>
            </aside>
            <main id="main" className="auth-form-side">
                <a className="auth-back" href="/">
                    ← Back to the studio
                </a>
                <div className="auth-form-card">
                    <p className="eyebrow">{adminSetup ? 'Administrator account' : 'Your learning account'}</p>
                    {title && <h1>{title}</h1>}
                    {description && (
                        <p className="auth-description">{description}</p>
                    )}
                    {children}
                </div>
                <footer>
                    <a href="/support">Need a hand?</a>
                    <a href="/privacy">Privacy</a>
                    <a href="/terms">Terms</a>
                </footer>
            </main>
        </div>
    );
}
