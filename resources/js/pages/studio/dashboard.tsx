import { Link, usePage } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
type Enrollment = {
    id: number;
    status: string;
    completed_at: string | null;
    required_count: number;
    completed_count: number;
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
    const { auth } = usePage<{ auth: { user: { name: string } } }>().props;
    const resume = enrollments.find(
        (e) => e.status === 'active' && !e.completed_at,
    );
    return (
        <StudioShell title="My learning">
            <header className="page-heading">
                <p className="eyebrow">A little progress, every day</p>
                <h1>Welcome back, {auth.user.name.split(' ')[0]}.</h1>
                <p>
                    Your next lesson is a good place to start. Make some space
                    for it.
                </p>
            </header>
            <div className="stat-strip">
                <div>
                    <strong>
                        {enrollments
                            .filter((e) => e.status === 'active')
                            .length.toString()
                            .padStart(2, '0')}
                    </strong>
                    <span>Courses in your studio</span>
                </div>
                <div>
                    <strong>
                        {enrollments
                            .filter((e) => e.completed_at)
                            .length.toString()
                            .padStart(2, '0')}
                    </strong>
                    <span>Chapters completed</span>
                </div>
                <div>
                    <strong>
                        {certificates.length.toString().padStart(2, '0')}
                    </strong>
                    <span>Completion credentials</span>
                </div>
            </div>
            {resume && (
                <div className="resume-panel">
                    <div className="resume-copy">
                        <p className="eyebrow">Continue your learning path</p>
                        <h2>{resume.course.title}</h2>
                        <Link
                            className="button"
                            href={`/learn/${resume.course.id}`}
                        >
                            Resume learning →
                        </Link>
                    </div>
                    <div className="resume-art" aria-hidden="true">
                        ↗
                    </div>
                </div>
            )}
            <section className="section">
                <div className="section-heading">
                    <h2>Your course collection.</h2>
                    <a href="/courses">Explore courses ↗</a>
                </div>
                <div className="course-grid">
                    {enrollments.map((e, i) => (
                        <article
                            className="course-card learning-card"
                            key={e.id}
                        >
                            <div className="learning-card-head">
                                <span className="course-number">
                                    {String(i + 1).padStart(2, '0')}
                                </span>
                                <span className="pill" data-status={e.status}>
                                    {e.completed_at ? 'Completed' : e.status}
                                </span>
                            </div>
                            <p className="eyebrow">{e.course.format} course</p>
                            <h3>{e.course.title}</h3>
                            <p>{e.course.summary}</p>
                            <div className="progress-meta">
                                <span>
                                    {e.completed_count} of {e.required_count}{' '}
                                    required lessons
                                </span>
                                <span>
                                    {e.required_count
                                        ? Math.round(
                                              (e.completed_count /
                                                  e.required_count) *
                                                  100,
                                          )
                                        : 0}
                                    %
                                </span>
                            </div>
                            <progress
                                value={e.completed_count}
                                max={e.required_count || 1}
                                aria-label={`${e.course.title} completion`}
                            />
                            <div className="actions">
                                {e.status === 'active' ? (
                                    <Link
                                        className="button secondary"
                                        href={`/learn/${e.course.id}`}
                                    >
                                        {e.completed_at
                                            ? 'Revisit classroom'
                                            : 'Open classroom'}{' '}
                                        →
                                    </Link>
                                ) : (
                                    <p className="fine">
                                        Access is {e.status}.{' '}
                                        <a href="/support">Contact support</a>.
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
                            Choose a course that sparks your curiosity. Your
                            lessons and progress will live here.
                        </p>
                        <a className="button" href="/courses">
                            Find a course →
                        </a>
                    </div>
                )}
            </section>
            <section className="section">
                <div className="section-heading">
                    <h2>On your calendar.</h2>
                    <Link href="/schedule">Full schedule ↗</Link>
                </div>
                {sessions.length === 0 && (
                    <p className="fine">
                        No upcoming live sessions. There’s still room to learn
                        at your own pace.
                    </p>
                )}
                {sessions.map((s) => (
                    <div className="studio-row" key={s.id}>
                        <div>
                            <p className="eyebrow">Live learning</p>
                            <h3>{s.title}</h3>
                            <p>
                                {new Date(s.starts_at).toLocaleString(
                                    undefined,
                                    { timeZone: s.timezone },
                                )}{' '}
                                · {s.timezone}
                            </p>
                        </div>
                        <Link
                            className="button secondary"
                            href={`/learn/${s.course_id}/${s.lesson_id}`}
                        >
                            View session →
                        </Link>
                    </div>
                ))}
            </section>
            <section className="section">
                <div className="section-heading">
                    <h2>Progress worth keeping.</h2>
                    <Link href="/credentials">All certificates ↗</Link>
                </div>
                {certificates.length === 0 && (
                    <p className="fine">
                        Complete an eligible course to request your Certificate
                        of Completion.
                    </p>
                )}
                {certificates.map((c) => (
                    <div className="studio-row" key={c.credential_id}>
                        <div>
                            <h3>{c.course_title}</h3>
                            <p>{c.status}</p>
                        </div>
                        <Link href={`/certificates/${c.credential_id}`}>
                            View certificate →
                        </Link>
                    </div>
                ))}
            </section>
        </StudioShell>
    );
}
