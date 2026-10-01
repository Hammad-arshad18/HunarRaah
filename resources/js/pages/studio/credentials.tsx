import { Link, router } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
import Pagination, { type PageLink } from '@/components/studio-pagination';
export default function Credentials({
    certificates,
    eligible,
}: {
    certificates: {
        links: PageLink[];
        data: {
            credential_id: string;
            course_title: string;
            status: string;
            generation_status: string;
            issued_at: string;
        }[];
    };
    eligible: { id: number; title: string }[];
}) {
    return (
        <StudioShell title="Certificates">
            <header className="page-heading">
                <p className="eyebrow">A record of your dedication</p>
                <h1>Chapters completed.</h1>
                <p>
                    Download your credentials and choose which ones you’d like
                    to share.
                </p>
            </header>
            {eligible.map((e) => (
                <div className="studio-row" key={e.id}>
                    <div>
                        <span className="pill">Ready to request</span>
                        <h3>{e.title}</h3>
                    </div>
                    <button
                        className="button"
                        onClick={() =>
                            router.post(`/enrollments/${e.id}/certificate`)
                        }
                    >
                        Request certificate →
                    </button>
                </div>
            ))}
            {certificates.data.map((c, i) => (
                <div className="studio-row" key={c.credential_id}>
                    <div>
                        <p className="eyebrow">
                            {String(i + 1).padStart(2, '0')} / Certificate of
                            Completion
                        </p>
                        <h3>{c.course_title}</h3>
                        <p>
                            Issued {new Date(c.issued_at).toLocaleDateString()}{' '}
                            · {c.status} · PDF {c.generation_status}
                        </p>
                    </div>
                    <Link
                        className="button secondary"
                        href={`/certificates/${c.credential_id}`}
                    >
                        View credential →
                    </Link>
                </div>
            ))}
            {!certificates.data.length && !eligible.length && (
                <div className="empty">
                    <h3>One lesson closer.</h3>
                    <p>
                        Complete the required lessons in an eligible course.
                        Then request your certificate here.
                    </p>
                    <Link className="button" href="/dashboard">
                        Back to learning →
                    </Link>
                </div>
            )}
            <Pagination links={certificates.links} />
        </StudioShell>
    );
}
