import { useForm } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
import Pagination, { type PageLink } from '@/components/studio-pagination';
type Props = {
    students: {
        links?: PageLink[];
        data: {
            id: number;
            name: string;
            email: string;
            suspended_at: string | null;
        }[];
    };
    orders: {
        links?: PageLink[];
        data: {
            id: number;
            public_reference: string;
            payment_status: string;
            dispute_status: string;
            financial_exception: boolean;
        }[];
    };
    enrollments: {
        links?: PageLink[];
        data: {
            id: number;
            status: string;
            user: { name: string };
            course: { title: string };
        }[];
    };
    certificates: {
        links?: PageLink[];
        data: {
            id: number;
            credential_id: string;
            learner_name: string;
            course_title: string;
            status: string;
        }[];
    };
    courses: { id: number; title: string }[];
};
function Operation({
    url,
    fields,
    label,
}: {
    url: string;
    fields: Record<string, string | number | boolean>;
    label: string;
}) {
    const form = useForm<
        Record<string, string | number | boolean> & { reason: string }
    >({ ...fields, reason: '' });
    return (
        <form
            className="actions"
            onSubmit={(e) => {
                e.preventDefault();
                form.post(url);
            }}
        >
            {Object.entries(fields)
                .filter(([key]) => key === 'learner_name')
                .map(([key]) => (
                    <label key={key}>
                        Corrected learner name
                        <input
                            required
                            value={String(form.data[key])}
                            onChange={(e) => form.setData(key, e.target.value)}
                        />
                    </label>
                ))}
            <label>
                Reason
                <input
                    required
                    value={form.data.reason}
                    onChange={(e) => form.setData('reason', e.target.value)}
                />
            </label>
            <button className="button secondary" disabled={form.processing}>
                {label}
            </button>
            {Object.values(form.errors).map((e, i) => (
                <p className="error" key={i}>
                    {e}
                </p>
            ))}
        </form>
    );
}
export default function Operations(p: Props) {
    const grant = useForm({ user_id: '', course_id: '', reason: '' });
    return (
        <StudioShell title="Operations">
            <section className="section">
                <p className="eyebrow">Administration</p>
                <h1>Access & records.</h1>
                <p>
                    Refunds are performed in Stripe Dashboard. Reconcile to
                    synchronize provider status.
                </p>
                <h2>Students</h2>
                <Pagination links={p.students.links} />
                {p.students.data.map((u) => (
                    <article className="path-panel" key={u.id}>
                        <h3>{u.name}</h3>
                        <p>{u.email}</p>
                        <Operation
                            url={`/admin/students/${u.id}/suspension`}
                            fields={{ suspended: !u.suspended_at }}
                            label={
                                u.suspended_at
                                    ? 'Restore account'
                                    : 'Suspend account'
                            }
                        />
                    </article>
                ))}
                <section className="section">
                    <h2>Complimentary enrollment</h2>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            grant.post(
                                `/admin/courses/${grant.data.course_id}/grant`,
                            );
                        }}
                    >
                        <label>
                            Student
                            <select
                                required
                                value={grant.data.user_id}
                                onChange={(e) =>
                                    grant.setData('user_id', e.target.value)
                                }
                            >
                                <option value="">Choose student</option>
                                {p.students.data.map((u) => (
                                    <option value={u.id} key={u.id}>
                                        {u.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            Course
                            <select
                                required
                                value={grant.data.course_id}
                                onChange={(e) =>
                                    grant.setData('course_id', e.target.value)
                                }
                            >
                                <option value="">Choose course</option>
                                {p.courses.map((c) => (
                                    <option value={c.id} key={c.id}>
                                        {c.title}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            Reason
                            <input
                                required
                                value={grant.data.reason}
                                onChange={(e) =>
                                    grant.setData('reason', e.target.value)
                                }
                            />
                        </label>
                        <button className="button">
                            Grant complimentary access
                        </button>
                        {Object.values(grant.errors).map((e, i) => (
                            <p className="error" key={i}>
                                {e}
                            </p>
                        ))}
                    </form>
                </section>
                <h2>Orders</h2>
                <Pagination links={p.orders.links} />
                {p.orders.data.map((o) => (
                    <article className="path-panel" key={o.id}>
                        <p>{o.public_reference}</p>
                        <p>
                            {o.payment_status} · Dispute: {o.dispute_status}
                            {o.financial_exception &&
                                ' · Financial exception: review duplicate charge'}
                        </p>
                        <Operation
                            url={`/admin/orders/${o.id}/reconcile`}
                            fields={{}}
                            label="Reconcile with Stripe"
                        />
                    </article>
                ))}
                <section className="section">
                    <h2>Entitlements</h2>
                    <Pagination links={p.enrollments.links} />
                    {p.enrollments.data.map((e) => (
                        <article className="path-panel" key={e.id}>
                            <h3>
                                {e.user.name} · {e.course.title}
                            </h3>
                            <p>{e.status}</p>
                            <Operation
                                url={`/admin/enrollments/${e.id}/restriction`}
                                fields={{
                                    status:
                                        e.status === 'active'
                                            ? 'suspended'
                                            : 'active',
                                }}
                                label={
                                    e.status === 'active'
                                        ? 'Suspend access'
                                        : 'Restore if eligible'
                                }
                            />
                        </article>
                    ))}
                </section>
                <h2>Credentials</h2>
                <Pagination links={p.certificates.links} />
                {p.certificates.data.map((c) => (
                    <article className="path-panel" key={c.id}>
                        <h3>{c.learner_name}</h3>
                        <p>
                            {c.course_title} · {c.status}
                        </p>
                        <Operation
                            url={`/admin/certificates/${c.id}/revoke`}
                            fields={{}}
                            label="Revoke credential"
                        />
                        {c.status === 'valid' && (
                            <Operation
                                url={`/admin/certificates/${c.id}/reissue`}
                                fields={{ learner_name: c.learner_name }}
                                label="Reissue corrected name"
                            />
                        )}
                    </article>
                ))}
            </section>
        </StudioShell>
    );
}
