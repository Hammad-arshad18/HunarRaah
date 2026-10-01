import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import StudioShell from '@/components/studio-shell';
type Order = {
    public_reference: string;
    payment_status: string;
    dispute_status: string;
    title_snapshot: string;
    total_minor: number;
    currency: string;
    course_id: number;
    has_access: boolean;
    refunded_minor: number;
};
export default function OrderPage({ order }: { order: Order }) {
    const [hasAccess, setHasAccess] = useState(order.has_access);
    const [refunded, setRefunded] = useState(order.refunded_minor);
    const [dispute, setDispute] = useState(order.dispute_status);
    const [status, setStatus] = useState(order.payment_status);
    const [polling, setPolling] = useState(status === 'pending');
    const [networkError, setNetworkError] = useState(false);
    useEffect(() => {
        if (status !== 'pending') return;
        let cancelled = false;
        let timer: ReturnType<typeof setTimeout>;
        let count = 0;
        const poll = async () => {
            try {
                const result = await fetch(
                    `/orders/${order.public_reference}`,
                    { headers: { Accept: 'application/json' } },
                );
                if (!result.ok) throw new Error();
                const data = await result.json();
                if (cancelled) return;
                setStatus(data.payment_status);
                setHasAccess(data.has_access);
                setRefunded(data.refunded_minor);
                setDispute(data.dispute_status);
                if (data.payment_status !== 'pending') {
                    setPolling(false);
                    return;
                }
            } catch {
                if (!cancelled) setNetworkError(true);
            }
            if (!cancelled && ++count < 5)
                timer = setTimeout(poll, Math.min(2000 * 2 ** count, 16000));
            else if (!cancelled) setPolling(false);
        };
        timer = setTimeout(poll, 2000);
        return () => {
            cancelled = true;
            clearTimeout(timer);
        };
    }, [order.public_reference, status]);
    return (
        <StudioShell title="Order status">
            <section className="section reading">
                <p className="eyebrow">Your order</p>
                <h1>
                    {status === 'paid'
                        ? 'Payment confirmed.'
                        : status === 'pending'
                          ? polling
                              ? 'Confirming payment…'
                              : 'Confirmation pending.'
                          : `Payment ${status}.`}
                </h1>
                <h3>{order.title_snapshot}</h3>
                <p>
                    {order.currency} {(order.total_minor / 100).toFixed(2)}
                </p>
                <p>Order reference: {order.public_reference}</p>
                {networkError && (
                    <p className="error" role="alert">
                        Status could not be refreshed. Check your connection and
                        reload.
                    </p>
                )}
                {refunded > 0 && (
                    <p>
                        Refunded: {order.currency} {(refunded / 100).toFixed(2)}
                    </p>
                )}
                {dispute !== 'none' && <p>Dispute status: {dispute}</p>}
                {hasAccess ? (
                    <Link className="button" href={`/learn/${order.course_id}`}>
                        Open classroom →
                    </Link>
                ) : (
                    <p className="note">
                        {status === 'paid'
                            ? 'Payment is recorded. Course access may be restricted by a refund, dispute or administrator decision. '
                            : 'Access is granted only after payment confirmation. '}
                        If you were charged, avoid another payment. Contact
                        support with this reference.
                    </p>
                )}
                <div className="actions">
                    <a href="/support">Contact support</a>
                    <Link href="/dashboard">My learning</Link>
                </div>
            </section>
        </StudioShell>
    );
}
