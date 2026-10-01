import { Link } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
export default function ErrorPage({
    status,
    message,
}: {
    status: number;
    message: string;
}) {
    return (
        <StudioShell title="Unable to open this page">
            <section className="page-heading">
                <p className="eyebrow">{status} / Let’s find your next step</p>
                <h1>This page isn’t available.</h1>
                <p>{message}</p>
            </section>
            <div className="actions">
                <Link href="/dashboard" className="button">
                    Back to learning →
                </Link>
                <a href="/support">Contact support</a>
            </div>
        </StudioShell>
    );
}
