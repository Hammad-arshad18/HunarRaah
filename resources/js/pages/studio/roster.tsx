import { useForm } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
import Pagination, { type PageLink } from '@/components/studio-pagination';
function Attendance({
    lessonId,
    enrollment,
}: {
    lessonId: number;
    enrollment: { id: number; user: { name: string } };
}) {
    const form = useForm({ reason: '' });
    return (
        <form
            className="path-panel"
            onSubmit={(e) => {
                e.preventDefault();
                form.post(
                    `/admin/lessons/${lessonId}/attendance/${enrollment.id}`,
                );
            }}
        >
            <h3>{enrollment.user.name}</h3>
            <label>
                Attendance evidence / correction reason
                <input
                    required
                    value={form.data.reason}
                    onChange={(e) => form.setData('reason', e.target.value)}
                />
            </label>
            <button className="button secondary">Record attended</button>
            {Object.values(form.errors).map((e, i) => (
                <p className="error" key={i}>
                    {e}
                </p>
            ))}
        </form>
    );
}
export default function Roster({
    lesson,
    enrollments,
}: {
    lesson: { id: number; title: string };
    enrollments: {
        links?: PageLink[];
        data: { id: number; user: { name: string } }[];
    };
}) {
    return (
        <StudioShell title="Attendance roster">
            <section className="section">
                <p className="eyebrow">Administrator attendance</p>
                <h1>{lesson.title}</h1>
                <p>
                    Joining is not evidence of attendance. Mark only attendance
                    verified by the training team.
                </p>
                <Pagination links={enrollments.links} />
                {enrollments.data.map((e) => (
                    <Attendance
                        key={e.id}
                        lessonId={lesson.id}
                        enrollment={e}
                    />
                ))}
                {enrollments.data.length === 0 && (
                    <p className="empty">No enrolled students yet.</p>
                )}
            </section>
        </StudioShell>
    );
}
