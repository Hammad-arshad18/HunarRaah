import { router, useForm } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
import LessonEditor from '@/components/lesson-editor';
import CourseImageUpload from '@/components/course-image-upload';
type Course = {
    id?: number;
    title: string;
    slug: string;
    summary: string;
    description: string;
    outcomes: string[];
    prerequisites: string;
    target_audience: string;
    level: string;
    duration_minutes: number;
    format: string;
    instructor_name: string;
    instructor_bio: string;
    price_minor: number;
    currency: string;
    enrollment_deadline: string;
    certificate_enabled: boolean;
    recording_alternative: boolean;
    accessible_content_confirmed: boolean;
    access_policy: string;
    completion_policy: string;
    modules?: {
        id: number;
        title: string;
        lessons: {
            id: number;
            title: string;
            summary: string | null;
            body: string | null;
            type: string;
            required: boolean;
            published: boolean;
            position: number;
            video_status: string;
        }[];
    }[];
};
const defaults: Course = {
    title: '',
    slug: '',
    summary: '',
    description: '',
    outcomes: [''],
    prerequisites: '',
    target_audience: '',
    level: 'Beginner',
    duration_minutes: 0,
    format: 'recorded',
    instructor_name: '',
    instructor_bio: '',
    price_minor: 0,
    currency: 'AED',
    enrollment_deadline: '',
    certificate_enabled: true,
    recording_alternative: false,
    accessible_content_confirmed: false,
    access_policy:
        'Access remains available until explicitly revoked under the published policy.',
    completion_policy:
        'Complete every required lesson. Text and recorded completion are self-attested.',
};
export default function CourseEditor({
    course,
    locked = false,
    embedded = false,
}: {
    course?: Course;
    locked?: boolean;
    embedded?: boolean;
}) {
    const { id, modules, ...initial } = course || defaults;
    const form = useForm(initial);
    const moduleForm = useForm({ title: '', position: 1 });
    const lessonForm = useForm({
        title: '',
        body: '',
        summary: '',
        type: 'text',
        required: true,
        published: true,
        position: 1,
    });
    const textFields = [
        'title',
        'slug',
        'summary',
        'description',
        'prerequisites',
        'target_audience',
        'level',
        'instructor_name',
        'instructor_bio',
        'access_policy',
        'completion_policy',
    ] as const;
    const body = (
        <section className="section">
            <h2>{id ? 'Edit the course.' : 'Start a new course.'}</h2>
            {locked && (
                <p className="note">
                    The curriculum and completion policy are locked because
                    students have enrolled.
                </p>
            )}
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    if (id) {
                        form.put(`/admin/courses/${id}`);
                    } else {
                        form.post('/admin/courses');
                    }
                }}
            >
                <div className="editor-grid">
                    {textFields.map((key) => (
                        <div key={key}>
                            <label htmlFor={key}>
                                {key.replaceAll('_', ' ')}
                            </label>
                            {[
                                'description',
                                'summary',
                                'instructor_bio',
                                'access_policy',
                                'completion_policy',
                            ].includes(key) ? (
                                <textarea
                                    id={key}
                                    value={form.data[key] || ''}
                                    onChange={(e) =>
                                        form.setData(key, e.target.value)
                                    }
                                />
                            ) : (
                                <input
                                    id={key}
                                    value={form.data[key] || ''}
                                    onChange={(e) =>
                                        form.setData(key, e.target.value)
                                    }
                                />
                            )}
                            <p className="error">{form.errors[key]}</p>
                        </div>
                    ))}
                    <div>
                        <label htmlFor="outcomes">
                            Learning outcomes (one per line)
                        </label>
                        <textarea
                            id="outcomes"
                            value={form.data.outcomes.join('\n')}
                            onChange={(e) =>
                                form.setData(
                                    'outcomes',
                                    e.target.value.split('\n'),
                                )
                            }
                        />
                    </div>
                    <div>
                        <label htmlFor="format">Format</label>
                        <select
                            id="format"
                            value={form.data.format}
                            onChange={(e) =>
                                form.setData('format', e.target.value)
                            }
                        >
                            {['recorded', 'live', 'hybrid'].map((f) => (
                                <option key={f}>{f}</option>
                            ))}
                        </select>
                    </div>
                    {(['price_minor', 'duration_minutes'] as const).map(
                        (key) => (
                            <div key={key}>
                                <label htmlFor={key}>
                                    {key === 'price_minor'
                                        ? 'Price in minor units (49900 = AED 499.00)'
                                        : 'Duration in minutes'}
                                </label>
                                <input
                                    id={key}
                                    type="number"
                                    min="0"
                                    value={form.data[key]}
                                    onChange={(e) =>
                                        form.setData(
                                            key,
                                            Number(e.target.value),
                                        )
                                    }
                                />
                            </div>
                        ),
                    )}
                    <div>
                        <label htmlFor="deadline">
                            Enrollment deadline (UTC)
                        </label>
                        <input
                            id="deadline"
                            type="datetime-local"
                            value={form.data.enrollment_deadline || ''}
                            onChange={(e) =>
                                form.setData(
                                    'enrollment_deadline',
                                    e.target.value,
                                )
                            }
                        />
                    </div>
                    {(
                        [
                            'certificate_enabled',
                            'recording_alternative',
                            'accessible_content_confirmed',
                        ] as const
                    ).map((key) => (
                        <label key={key}>
                            <input
                                type="checkbox"
                                checked={form.data[key]}
                                onChange={(e) =>
                                    form.setData(key, e.target.checked)
                                }
                            />{' '}
                            {key.replaceAll('_', ' ')}
                        </label>
                    ))}
                </div>
                <div className="actions">
                    <button className="button" disabled={form.processing}>
                        Save draft
                    </button>
                    {id && (
                        <>
                            <button
                                type="button"
                                className="button secondary"
                                onClick={() =>
                                    router.post(`/admin/courses/${id}/publish`)
                                }
                            >
                                Validate and publish
                            </button>
                            <button
                                type="button"
                                className="button secondary"
                                onClick={() =>
                                    router.post(`/admin/courses/${id}/archive`)
                                }
                            >
                                Archive
                            </button>
                            <button
                                type="button"
                                className="button secondary"
                                onClick={() =>
                                    router.post(
                                        `/admin/courses/${id}/duplicate`,
                                    )
                                }
                            >
                                Duplicate into draft
                            </button>
                            <a href={`/admin/courses/${id}/preview`}>
                                Audited preview
                            </a>
                        </>
                    )}
                </div>
                {Object.entries(form.errors).map(([key, error]) => (
                    <p className="error" role="alert" key={key}>
                        {key}: {error}
                    </p>
                ))}
            </form>
            {id && <CourseImageUpload courseId={id} />}
            {id && (
                <section className="section">
                    <h2>The curriculum.</h2>
                    {modules?.map((m) => (
                        <section key={m.id}>
                            <h3>{m.title}</h3>
                            {m.lessons.map((l) => (
                                <LessonEditor
                                    key={l.id}
                                    lesson={l}
                                    courseId={id}
                                    locked={locked}
                                />
                            ))}
                            {!locked && (
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        lessonForm.post(
                                            `/admin/courses/${id}/modules/${m.id}/lessons`,
                                        );
                                    }}
                                >
                                    <label>
                                        Lesson title
                                        <input
                                            value={lessonForm.data.title}
                                            onChange={(e) =>
                                                lessonForm.setData(
                                                    'title',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                    <label>
                                        Lesson type
                                        <select
                                            value={lessonForm.data.type}
                                            onChange={(e) => {
                                                lessonForm.setData(
                                                    'type',
                                                    e.target.value,
                                                );
                                                lessonForm.setData(
                                                    'published',
                                                    e.target.value === 'text',
                                                );
                                            }}
                                        >
                                            {['text', 'video', 'live'].map(
                                                (t) => (
                                                    <option key={t}>{t}</option>
                                                ),
                                            )}
                                        </select>
                                    </label>
                                    <label>
                                        Position
                                        <input
                                            type="number"
                                            min="1"
                                            value={lessonForm.data.position}
                                            onChange={(e) =>
                                                lessonForm.setData(
                                                    'position',
                                                    Number(e.target.value),
                                                )
                                            }
                                        />
                                    </label>
                                    <label>
                                        Text lesson content (Markdown)
                                        <textarea
                                            value={lessonForm.data.body}
                                            onChange={(e) =>
                                                lessonForm.setData(
                                                    'body',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                    <button className="button secondary">
                                        Add lesson
                                    </button>
                                </form>
                            )}
                        </section>
                    ))}
                    {!locked && (
                        <form
                            className="section"
                            onSubmit={(e) => {
                                e.preventDefault();
                                moduleForm.post(`/admin/courses/${id}/modules`);
                            }}
                        >
                            <label>
                                New module title
                                <input
                                    value={moduleForm.data.title}
                                    onChange={(e) =>
                                        moduleForm.setData(
                                            'title',
                                            e.target.value,
                                        )
                                    }
                                />
                            </label>
                            <label>
                                Position
                                <input
                                    type="number"
                                    min="1"
                                    value={moduleForm.data.position}
                                    onChange={(e) =>
                                        moduleForm.setData(
                                            'position',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                            </label>
                            <button className="button">Add module</button>
                        </form>
                    )}
                </section>
            )}
        </section>
    );
    return embedded ? (
        body
    ) : (
        <StudioShell title="Course editor">{body}</StudioShell>
    );
}
