import { Link } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
import Pagination, { type PageLink } from '@/components/studio-pagination';
export default function Purchases({
    orders,
}: {
    orders: {
        links: PageLink[];
        data: {
            public_reference: string;
            title_snapshot: string;
            payment_status: string;
            dispute_status: string;
            total_minor: number;
            refunded_minor: number;
            currency: string;
            created_at: string;
        }[];
    };
}) {
    return (
        <StudioShell title="Purchases">
            <header className="page-heading">
                <p className="eyebrow">Everything in one place</p>
                <h1>Your purchases.</h1>
                <p>
                    Payment confirmations, order references and the status of
                    your course purchases.
                </p>
            </header>
            {orders.data.length ? (
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Course & reference</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.map((o) => (
                                <tr key={o.public_reference}>
                                    <td>
                                        {o.title_snapshot}
                                        <p
                                            className="fine"
                                            style={{ overflowWrap: 'anywhere' }}
                                        >
                                            {o.public_reference}
                                        </p>
                                    </td>
                                    <td>
                                        {new Date(
                                            o.created_at,
                                        ).toLocaleDateString()}
                                    </td>
                                    <td>
                                        {o.currency}{' '}
                                        {(o.total_minor / 100).toFixed(2)}
                                        {o.refunded_minor > 0 && (
                                            <p className="fine">
                                                Refunded:{' '}
                                                {(
                                                    o.refunded_minor / 100
                                                ).toFixed(2)}
                                            </p>
                                        )}
                                    </td>
                                    <td>
                                        <span
                                            className="pill"
                                            data-status={o.payment_status}
                                        >
                                            {o.payment_status}
                                        </span>
                                        {o.dispute_status !== 'none' && (
                                            <p className="fine">
                                                Dispute: {o.dispute_status}
                                            </p>
                                        )}
                                    </td>
                                    <td>
                                        <Link
                                            href={`/orders/${o.public_reference}`}
                                        >
                                            View order →
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="empty">
                    <h3>A clean page.</h3>
                    <p>
                        You haven’t purchased a course yet. Free enrollments
                        appear in My learning.
                    </p>
                    <a className="button" href="/courses">
                        Explore courses →
                    </a>
                </div>
            )}
            <Pagination links={orders.links} />
        </StudioShell>
    );
}
