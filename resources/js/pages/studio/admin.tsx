import { Link } from '@inertiajs/react';
import StudioShell from '@/components/studio-shell';
import CourseEditor from './course-editor';
import Pagination, { type PageLink } from '@/components/studio-pagination';
export default function Admin({
    courses,
}: {
    courses: {
        links?: PageLink[];
        data: {
            id: number;
            title: string;
            status: string;
            price_minor: number;
            currency: string;
        }[];
    };
}) {
    return (
        <StudioShell title="Course administration">
            <section className="section">
                <p className="eyebrow">Administration</p>
                <h1>The teaching desk.</h1>
                <Link href="/admin/operations">
                    Students, access and financial records →
                </Link>
                <p>
                    Create a thoughtful learning journey, then review it before
                    publishing.
                </p>
                <form
                    action="/admin/courses"
                    method="get"
                    className="filter-bar"
                >
                    <label>
                        Status
                        <select name="status">
                            <option value="">All statuses</option>
                            {['draft', 'published', 'archived'].map((s) => (
                                <option key={s}>{s}</option>
                            ))}
                        </select>
                    </label>
                    <button className="button secondary">Filter courses</button>
                </form>
                <Pagination links={courses.links} />
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {courses.data.map((c) => (
                                <tr key={c.id}>
                                    <td>{c.title}</td>
                                    <td>{c.status}</td>
                                    <td>
                                        {c.currency}{' '}
                                        {(c.price_minor / 100).toFixed(2)}
                                    </td>
                                    <td>
                                        <Link
                                            href={`/admin/courses/${c.id}/edit`}
                                        >
                                            Edit course →
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {courses.data.length === 0 && (
                    <p className="empty">
                        No courses yet. Start with a draft below.
                    </p>
                )}
            </section>
            <CourseEditor embedded />
        </StudioShell>
    );
}
