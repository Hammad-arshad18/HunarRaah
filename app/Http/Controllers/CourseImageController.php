<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CourseImageController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);
        $data = $request->validate(['slot' => 'required|in:cover,instructor_photo', 'image' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $bytes = (string) file_get_contents($request->file('image')->getRealPath());
        $size = getimagesizefromstring($bytes);
        abort_unless($size && $size[0] * $size[1] <= 12000000 && $size[0] <= 6000 && $size[1] <= 6000, 422, 'Image dimensions are too large.');
        $image = imagecreatefromstring($bytes);
        abort_unless($image !== false, 422, 'Invalid image.');
        ob_start();
        imagepng($image);
        $clean = ob_get_clean();
        imagedestroy($image);
        $path = 'course-images/'.bin2hex(random_bytes(16)).'.png';
        abort_unless((bool) Storage::disk('public')->put($path, (string) $clean), 503);
        $course->update([$data['slot'].'_path' => $path]);
        DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'action' => 'course.image_updated', 'subject_type' => 'course', 'subject_id' => $course->id, 'created_at' => now()]);

        return back();
    }
}
