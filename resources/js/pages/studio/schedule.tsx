import { Link } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
import Pagination, { type PageLink } from '@/components/studio-pagination';
export default function Schedule({
    sessions,
}: {
    sessions: {
        links: PageLink[];
        data: {
            id: number;
            title: string;
            course_title: string;
            course_id: number;
            lesson_id: number;
            starts_at: string;
            ends_at: string;
            timezone: string;
            status: string;
            message: string | null;
        }[];
    };
}) {
    return (
        <StudioShell title="Live schedule">
            <header className="page-heading">
                <p className="eyebrow">A time to learn together</p>
                <h1>Meet you in class.</h1>
                <p>
                    Your shared course schedule. Open a lesson to join when the
                    session window begins.
                </p>
            </header>
            {sessions.data.map((s) => (
                <div className="studio-row" key={s.id}>
                    <div>
                        <p className="eyebrow">{s.course_title}</p>
                        <h3>{s.title}</h3>
                        <p>
                            {new Date(s.starts_at).toLocaleString(undefined, {
                                timeZone: s.timezone,
                            })}{' '}
                            · {s.timezone}
                        </p>
                        <p>
                            Your local time:{' '}
                            {new Date(s.starts_at).toLocaleString()}
                        </p>
                        {s.message && <p>{s.message}</p>}
                        <span className="pill">{s.status}</span>
                    </div>
                    <Link
                        className="button secondary"
                        href={`/learn/${s.course_id}/${s.lesson_id}`}
                    >
                        Open session lesson →
                    </Link>
                </div>
            ))}
            {!sessions.data.length && (
                <div className="empty">
                    <h3>Your calendar has some space.</h3>
                    <p>
                        Live sessions for your enrolled courses will appear here
                        as they are scheduled.
                    </p>
                    <Link href="/dashboard" className="button">
                        My learning →
                    </Link>
                </div>
            )}
            <Pagination links={sessions.links} />
        </StudioShell>
    );
}
