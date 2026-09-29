import { Link } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
type Enrollment = {
    id: number;
    status: string;
    completed_at: string | null;
    course: { id: number; title: string; summary: string; format: string };
};
export default function Dashboard({
    enrollments,
    certificates,
    sessions,
}: {
    enrollments: Enrollment[];
    certificates: {
        credential_id: string;
        course_title: string;
        status: string;
    }[];
    sessions: {
        id: number;
        title: string;
        starts_at: string;
        timezone: string;
        course_id: number;
        lesson_id: number;
    }[];
}) {
    const resume = enrollments.find(
        (e) => e.status === 'active' && !e.completed_at,
    );
    return (
        <StudioShell title="My learning">
            <section className="section">
                <p className="eyebrow">Your learning studio</p>
                <h1>
                    Keep your
                    <br />
                    curiosity moving.
                </h1>
                {resume && (
                    <div className="path-panel actions">
                        <div>
                            <p className="eyebrow">
                                Pick up where you left off
                            </p>
                            <h2>{resume.course.title}</h2>
                        </div>
                        <Link
                            className="button"
                            href={`/learn/${resume.course.id}`}
                        >
                            Resume learning →
                        </Link>
                    </div>
                )}
            </section>
            <section className="section">
                <h2>Your courses.</h2>
                <div className="course-grid">
                    {enrollments.map((e) => (
                        <article key={e.id} className="course-card card-body">
                            <p className="eyebrow">{e.course.format}</p>
                            <h3>{e.course.title}</h3>
                            <p>{e.course.summary}</p>
                            <span className="status">
                                {e.completed_at ? 'Completed' : e.status}
                            </span>
                            <div className="actions">
                                {e.status === 'active' ? (
                                    <Link
                                        className="button"
                                        href={`/learn/${e.course.id}`}
                                    >
                                        Open classroom →
                                    </Link>
                                ) : (
                                    <p>
                                        Access is {e.status}. Contact support
                                        for help.
                                    </p>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
                {enrollments.length === 0 && (
                    <div className="empty">
                        <h3>Your next chapter is waiting.</h3>
                        <p>
                            Explore the course collection and choose your
                            starting point.
                        </p>
                        <a className="button" href="/courses">
                            Find a course →
                        </a>
                    </div>
                )}
            </section>
            <section className="section">
                <h2>Next live sessions.</h2>
                {sessions.length === 0 && <p>No upcoming live sessions.</p>}
                {sessions.map((s) => (
                    <div className="path-panel" key={s.id}>
                        <h3>{s.title}</h3>
                        <p>
                            {new Date(s.starts_at).toLocaleString(undefined, {
                                timeZone: s.timezone,
                            })}{' '}
                            ({s.timezone})
                        </p>
                        <Link href={`/learn/${s.course_id}/${s.lesson_id}`}>
                            Open session lesson →
                        </Link>
                    </div>
                ))}
            </section>
            <section className="section">
                <h2>Your credentials.</h2>
                {certificates.length === 0 && (
                    <p>
                        Complete an eligible course to request your certificate.
                    </p>
                )}
                {certificates.map((c) => (
                    <div className="path-panel" key={c.credential_id}>
                        <h3>{c.course_title}</h3>
                        <p>{c.status}</p>
                        <Link href={`/certificates/${c.credential_id}`}>
                            View certificate →
                        </Link>
                    </div>
                ))}
            </section>
        </StudioShell>
    );
}
