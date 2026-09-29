import { useForm } from '@inertiajs/react';
export default function CourseImageUpload({ courseId }: { courseId: number }) {
    const form = useForm<{ slot: string; image: File | null }>({
        slot: 'cover',
        image: null,
    });
    return (
        <form
            className="section"
            onSubmit={(e) => {
                e.preventDefault();
                form.post(`/admin/courses/${courseId}/image`, {
                    forceFormData: true,
                });
            }}
        >
            <h3>Course imagery</h3>
            <p>
                Upload owned JPG, PNG or WebP imagery. The server validates and
                re-encodes images.
            </p>
            <label>
                Placement
                <select
                    value={form.data.slot}
                    onChange={(e) => form.setData('slot', e.target.value)}
                >
                    <option value="cover">Course cover</option>
                    <option value="instructor_photo">Instructor photo</option>
                </select>
            </label>
            <label>
                Image (maximum 5 MB)
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    required
                    onChange={(e) =>
                        form.setData('image', e.target.files?.[0] || null)
                    }
                />
            </label>
            <button className="button secondary" disabled={form.processing}>
                Upload image
            </button>
            {Object.values(form.errors).map((e, i) => (
                <p className="error" key={i}>
                    {e}
                </p>
            ))}
        </form>
    );
}
