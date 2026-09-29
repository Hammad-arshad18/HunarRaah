import { useForm } from '@inertiajs/react';
type Lesson = {
    id: number;
    title: string;
    summary: string | null;
    body: string | null;
    type: string;
    required: boolean;
    published: boolean;
    position: number;
    video_status: string;
};
export default function LessonEditor({
    lesson: l,
    courseId,
    locked,
}: {
    lesson: Lesson;
    courseId: number;
    locked: boolean;
}) {
    const form = useForm({
        title: l.title,
        summary: l.summary || '',
        body: l.body || '',
        type: l.type,
        required: l.required,
        published: l.published,
        position: l.position,
        reason: '',
    });
    const video = useForm({ video_uid: '', reason: '' });
    const session = useForm({
        provider: '',
        join_url: '',
        starts_at: '',
        ends_at: '',
        timezone: 'Asia/Dubai',
        status: 'scheduled',
        message: '',
        reason: '',
    });
    return (
        <details className="path-panel">
            <summary>
                {l.title} · {l.type} · {l.published ? 'Published' : 'Draft'}
            </summary>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.put(`/admin/courses/${courseId}/lessons/${l.id}`);
                }}
            >
                <label>
                    Title
                    <input
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                    />
                </label>
                <label>
                    Summary
                    <input
                        value={form.data.summary}
                        onChange={(e) =>
                            form.setData('summary', e.target.value)
                        }
                    />
                </label>
                <label>
                    Text (safe Markdown)
                    <textarea
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                    />
                </label>
                <label>
                    Type
                    <select
                        disabled={locked}
                        value={form.data.type}
                        onChange={(e) => form.setData('type', e.target.value)}
                    >
                        {['text', 'video', 'live'].map((t) => (
                            <option key={t}>{t}</option>
                        ))}
                    </select>
                </label>
                <label>
                    Position
                    <input
                        disabled={locked}
                        type="number"
                        min="1"
                        value={form.data.position}
                        onChange={(e) =>
                            form.setData('position', Number(e.target.value))
                        }
                    />
                </label>
                <label>
                    <input
                        disabled={locked}
                        type="checkbox"
                        checked={form.data.required}
                        onChange={(e) =>
                            form.setData('required', e.target.checked)
                        }
                    />{' '}
                    Required
                </label>
                <label>
                    <input
                        disabled={locked}
                        type="checkbox"
                        checked={form.data.published}
                        onChange={(e) =>
                            form.setData('published', e.target.checked)
                        }
                    />{' '}
                    Published
                </label>
                <label>
                    Correction reason
                    <input
                        required
                        value={form.data.reason}
                        onChange={(e) => form.setData('reason', e.target.value)}
                    />
                </label>
                <button className="button" disabled={form.processing}>
                    Save lesson
                </button>
                {Object.values(form.errors).map((error, i) => (
                    <p role="alert" className="error" key={i}>
                        {error}
                    </p>
                ))}
            </form>
            {l.type !== 'text' && (
                <form
                    className="section"
                    onSubmit={(e) => {
                        e.preventDefault();
                        video.post(`/admin/lessons/${l.id}/video`);
                    }}
                >
                    <h3>Private recording</h3>
                    <p>
                        Provider status: {l.video_status}. Configure signed
                        playback and allowed origins in Stream.
                    </p>
                    <label>
                        Stream video UID
                        <input
                            required
                            value={video.data.video_uid}
                            onChange={(e) =>
                                video.setData('video_uid', e.target.value)
                            }
                        />
                    </label>
                    <label>
                        Attachment reason
                        <input
                            required
                            value={video.data.reason}
                            onChange={(e) =>
                                video.setData('reason', e.target.value)
                            }
                        />
                    </label>
                    <button className="button secondary">
                        Validate and attach
                    </button>
                    {Object.values(video.errors).map((error, i) => (
                        <p role="alert" className="error" key={i}>
                            {error}
                        </p>
                    ))}
                </form>
            )}
            {l.type === 'live' && (
                <>
                    <form
                        className="section"
                        onSubmit={(e) => {
                            e.preventDefault();
                            session.post(`/admin/lessons/${l.id}/session`);
                        }}
                    >
                        <h3>Shared live schedule</h3>
                        <p>
                            Enter UTC instants. The IANA timezone controls
                            display.
                        </p>
                        {(
                            [
                                'provider',
                                'join_url',
                                'timezone',
                                'message',
                                'reason',
                            ] as const
                        ).map((key) => (
                            <label key={key}>
                                {key.replaceAll('_', ' ')}
                                <input
                                    required={key !== 'message'}
                                    value={session.data[key]}
                                    onChange={(e) =>
                                        session.setData(key, e.target.value)
                                    }
                                />
                            </label>
                        ))}
                        {(['starts_at', 'ends_at'] as const).map((key) => (
                            <label key={key}>
                                {key.replaceAll('_', ' ')} (UTC)
                                <input
                                    type="datetime-local"
                                    required
                                    value={session.data[key]}
                                    onChange={(e) =>
                                        session.setData(key, e.target.value)
                                    }
                                />
                            </label>
                        ))}
                        <label>
                            Status
                            <select
                                value={session.data.status}
                                onChange={(e) =>
                                    session.setData('status', e.target.value)
                                }
                            >
                                {['scheduled', 'cancelled', 'completed'].map(
                                    (s) => (
                                        <option key={s}>{s}</option>
                                    ),
                                )}
                            </select>
                        </label>
                        <button className="button secondary">
                            Save session change
                        </button>
                        {Object.values(session.errors).map((error, i) => (
                            <p role="alert" className="error" key={i}>
                                {error}
                            </p>
                        ))}
                    </form>
                    <a
                        className="button secondary"
                        href={`/admin/lessons/${l.id}/roster`}
                    >
                        Attendance roster →
                    </a>
                </>
            )}
        </details>
    );
}
