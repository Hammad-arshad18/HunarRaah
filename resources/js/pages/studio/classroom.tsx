import { Link, router, useForm } from '@inertiajs/react';
import PrivatePlayer from '@/components/private-player';
import StudioShell from '@/components/studio-shell';
type Lesson = {
    id: number;
    title: string;
    type: string;
    html: string | null;
    video_status: string;
    complete: boolean;
    position_seconds: number;
    session: null | {
        id: number;
        starts_at: string;
        ends_at: string;
        timezone: string;
        status: string;
        message: string | null;
    };
};
type Module = {
    id: number;
    title: string;
    lessons: {
        id: number;
        title: string;
        type: string;
        required: boolean;
        complete: boolean;
    }[];
};
export default function Classroom({
    course,
    lesson,
    modules,
    progress,
    enrollment,
}: {
    course: { id: number; title: string };
    lesson: Lesson;
    modules: Module[];
    progress: { completed: number; required: number };
    enrollment: { id: number; completed_at: string | null };
}) {
    const complete = useForm({ confirmed: false });
    const all = modules.flatMap((m) => m.lessons);
    const index = all.findIndex((l) => l.id === lesson.id);
    return (
        <StudioShell title={lesson.title}>
            <div className="classroom">
                <aside className="lesson-nav">
                    <p className="eyebrow">Your learning path</p>
                    <h3>{course.title}</h3>
                    <p>
                        {progress.completed} / {progress.required} required
                        lessons completed
                    </p>
                    <progress
                        value={progress.completed}
                        max={progress.required || 1}
                        aria-label="Required lesson progress"
                    />
                    {modules.map((m, i) => (
                        <div key={m.id}>
                            <h4>
                                {String(i + 1).padStart(2, '0')} / {m.title}
                            </h4>
                            {m.lessons.map((l) => (
                                <Link
                                    key={l.id}
                                    href={`/learn/${course.id}/${l.id}`}
                                    aria-current={
                                        l.id === lesson.id ? 'page' : undefined
                                    }
                                >
                                    {l.complete ? '✓ ' : '○ '}
                                    {l.title}
                                    {!l.required && ' · Optional'}
                                </Link>
                            ))}
                        </div>
                    ))}
                </aside>
                <article className="lesson-stage">
                    <p className="eyebrow">{lesson.type} lesson</p>
                    <h1 style={{ fontSize: 'clamp(30px,4vw,48px)' }}>
                        {lesson.title}
                    </h1>
                    {lesson.html && (
                        <div
                            className="prose"
                            dangerouslySetInnerHTML={{ __html: lesson.html }}
                        />
                    )}
                    {lesson.type !== 'text' && (
                        <>
                            {lesson.video_status === 'ready' ? (
                                <PrivatePlayer
                                    key={lesson.id}
                                    lessonId={lesson.id}
                                    position={lesson.position_seconds}
                                />
                            ) : (
                                <div className="note">
                                    {lesson.video_status === 'failed'
                                        ? 'Recording unavailable. Contact support.'
                                        : 'Recording processing or not yet available.'}
                                </div>
                            )}
                        </>
                    )}
                    {lesson.session && (
                        <section className="section">
                            <h3>Live session</h3>
                            <p>
                                {new Date(
                                    lesson.session.starts_at,
                                ).toLocaleString(undefined, {
                                    timeZone: lesson.session.timezone,
                                })}{' '}
                                ({lesson.session.timezone})
                            </p>
                            <p>
                                Your local time:{' '}
                                {new Date(
                                    lesson.session.starts_at,
                                ).toLocaleString()}
                            </p>
                            <span className="status">
                                {lesson.session.status}
                            </span>
                            {lesson.session.message && (
                                <p>{lesson.session.message}</p>
                            )}
                            <button
                                className="button"
                                onClick={() =>
                                    router.post(
                                        `/live-sessions/${lesson.session!.id}/join`,
                                    )
                                }
                            >
                                Join session →
                            </button>
                            <p className="fine">
                                Joining opens 15 minutes before the session.
                                Joining does not mark attendance.
                            </p>
                        </section>
                    )}
                    {lesson.complete ? (
                        <p className="note">Lesson completed.</p>
                    ) : (
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                complete.post(`/lessons/${lesson.id}/complete`);
                            }}
                            className="section"
                        >
                            <label>
                                <input
                                    type="checkbox"
                                    checked={complete.data.confirmed}
                                    onChange={(e) =>
                                        complete.setData(
                                            'confirmed',
                                            e.target.checked,
                                        )
                                    }
                                />{' '}
                                I confirm I completed the material.
                            </label>
                            <button
                                disabled={complete.processing}
                                className="button"
                            >
                                Mark complete
                            </button>
                            {Object.values(complete.errors).map((error, i) => (
                                <p key={i} className="error" role="alert">
                                    {error}
                                </p>
                            ))}
                        </form>
                    )}
                    <nav className="actions" aria-label="Lesson navigation">
                        {all[index - 1] && (
                            <Link
                                className="button secondary"
                                href={`/learn/${course.id}/${all[index - 1].id}`}
                            >
                                ← Previous lesson
                            </Link>
                        )}
                        {all[index + 1] && (
                            <Link
                                className="button"
                                href={`/learn/${course.id}/${all[index + 1].id}`}
                            >
                                Next lesson →
                            </Link>
                        )}
                    </nav>
                    {enrollment.completed_at && (
                        <div className="section">
                            <h3>A chapter completed.</h3>
                            <button
                                className="button"
                                onClick={() =>
                                    router.post(
                                        `/enrollments/${enrollment.id}/certificate`,
                                    )
                                }
                            >
                                Request certificate →
                            </button>
                        </div>
                    )}
                </article>
            </div>
        </StudioShell>
    );
}
