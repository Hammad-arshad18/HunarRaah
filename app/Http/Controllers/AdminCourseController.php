<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminCourseController extends Controller
{
    public function reviseModule(Request $request, Course $course, Module $module): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($module->course_id === $course->id, 404);
        $data = $request->validate(['title' => 'required|string|max:200', 'position' => 'required|integer|min:1', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $course, $module, $data) {
            $c = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_if($c->enrollments()->exists() && $module->position != $data['position'], 409, 'Module order is locked after enrollment.');
            $module->update(['title' => $data['title'], 'position' => $data['position']]);
            $this->audit($request, $course, 'module.updated');
        });

        return back()->with('toast', ['type' => 'success', 'message' => 'Chapter saved.']);
    }

    public function removeModule(Request $request, Course $course, Module $module): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($module->course_id === $course->id, 404);
        $request->validate(['reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $course, $module) {
            $c = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_if($c->enrollments()->exists(), 409, 'Enrolled curriculum cannot be deleted.');
            abort_if($module->lessons()->exists(), 409, 'Remove the lessons before deleting this chapter.');
            $module->delete();
            $this->audit($request, $course, 'module.deleted');
        });

        return back();
    }

    public function removeLesson(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($lesson->module->course_id === $course->id, 404);
        $request->validate(['reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $course, $lesson) {
            $c = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_if($c->enrollments()->exists(), 409, 'Enrolled lessons cannot be deleted.');
            $lesson->session()->delete();
            $lesson->delete();
            $this->audit($request, $course, 'lesson.deleted');
        });

        return back();
    }

    public function availability(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);
        $data = $request->validate(['sales_visible' => 'required|boolean', 'takedown_reason' => 'nullable|string|max:1000', 'reason' => 'required|string|max:1000']);
        abort_if($data['sales_visible'] && $course->status !== 'published', 409, 'Publish the course before enabling sales.');
        $course->update(['sales_visible' => $data['sales_visible'], 'takedown_reason' => $data['takedown_reason'] ?: null]);
        $this->audit($request, $course, 'course.availability');

        return back()->with('toast', ['type' => 'success', 'message' => 'Availability updated.']);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = Course::create($request->validated());
        $this->audit($request, $course, 'course.created');

        return redirect('/admin/courses/'.$course->id.'/edit');
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);
        DB::transaction(function () use ($request, $course) {
            $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            if ($course->enrollments()->exists()) {
                foreach (['recording_alternative', 'completion_policy'] as $key) {
                    if ($request->validated($key) != $course->$key) {
                        throw ValidationException::withMessages([$key => 'Completion policy is locked after enrollment. Duplicate the course for structural revisions.']);
                    }
                }
            }
            $course->update($request->validated());
            $this->audit($request, $course, 'course.updated');
        });

        return back()->with('toast', ['type' => 'success', 'message' => 'Course details saved.']);
    }

    public function module(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);
        $data = $request->validate(['title' => 'required|string|max:200', 'position' => 'required|integer|min:1']);
        DB::transaction(function () use ($course, $data) {
            $locked = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->enrollments()->exists(), 409, 'Curriculum is locked.');
            $locked->modules()->create($data);
        });
        $this->audit($request, $course, 'module.created');

        return back();
    }

    public function lesson(Request $request, Course $course, Module $module): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($module->course_id === $course->id, 404);
        $data = $request->validate(['title' => 'required|string|max:200', 'summary' => 'nullable|string|max:500', 'type' => 'required|in:text,video,live', 'required' => 'required|boolean', 'published' => 'required|boolean', 'position' => 'required|integer|min:1', 'body' => 'nullable|string|max:100000']);
        abort_if($data['published'] && $data['type'] !== 'text', 422, 'Attach a ready video or valid live session before publishing.');
        DB::transaction(function () use ($course, $module, $data) {
            $locked = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->enrollments()->exists(), 409, 'Curriculum is locked.');
            $module->lessons()->create($data);
        });
        $this->audit($request, $course, 'lesson.created');

        return back();
    }

    public function publish(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);
        DB::transaction(function () use ($course, $request) {
            $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $lessons = $course->lessons()->get();
            $valid = $course->accessible_content_confirmed && $course->outcomes && $course->instructor_bio && $course->completion_policy && $course->access_policy && $course->currency === config('platform.currency') && $lessons->where('required', true)->count() > 0;
            foreach ($lessons as $lesson) {
                if ($lesson->required && ! $lesson->published) {
                    $valid = false;
                }
                if ($lesson->published && $lesson->type === 'video' && $lesson->video_status !== 'ready') {
                    $valid = false;
                }
                if ($lesson->published && $lesson->type === 'text' && ! trim($lesson->body ?? '')) {
                    $valid = false;
                }
                if ($lesson->published && $lesson->type === 'live' && ! $lesson->session) {
                    $valid = false;
                }
            }
            if (! $valid) {
                throw ValidationException::withMessages(['publish' => 'Confirm accessible content, required curriculum, instructor, access/completion policy and ready recordings or scheduled sessions.']);
            }
            $course->update(['status' => 'published', 'sales_visible' => true]);
            $this->audit($request, $course, 'course.published');
        });

        return back();
    }

    public function archive(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);
        $course->update(['status' => 'archived', 'sales_visible' => false]);
        $this->audit($request, $course, 'course.archived');

        return back();
    }

    public function reviseLesson(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($lesson->module->course_id === $course->id, 404);
        $data = $request->validate(['title' => 'required|string|max:200', 'summary' => 'nullable|string|max:500', 'body' => 'nullable|string|max:100000', 'type' => 'required|in:text,video,live', 'required' => 'required|boolean', 'published' => 'required|boolean', 'position' => 'required|integer|min:1', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $course, $lesson, $data) {
            $c = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            if ($c->enrollments()->exists()) {
                foreach (['required', 'type', 'position', 'published'] as $key) {
                    abort_if($data[$key] != $lesson->$key, 409, 'Curriculum structure is locked after enrollment.');
                }
            }
            abort_if($data['published'] && $data['type'] === 'text' && ! trim($data['body'] ?? ''), 422, 'A published text lesson needs content.');
            if ($data['published'] && $data['type'] === 'video') {
                abort_unless($lesson->video_status === 'ready', 422, 'Recording must be ready.');
            }if ($data['published'] && $data['type'] === 'live') {
                abort_unless($lesson->session !== null, 422, 'Schedule a live session first.');
            }$reason = $data['reason'];
            unset($data['reason']);
            $lesson->update($data);
            $this->audit($request, $course, 'lesson.updated');
            DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'action' => 'lesson.correction', 'subject_type' => 'lesson', 'subject_id' => $lesson->id, 'reason' => $reason, 'created_at' => now()]);
        });

        return back();
    }

    public function duplicate(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);
        $new = DB::transaction(function () use ($course) {
            $new = $course->replicate();
            $new->slug = $course->slug.'-revision-'.Str::lower(Str::random(6));
            $new->title = $course->title.' (revision)';
            $new->status = 'draft';
            $new->sales_visible = false;
            $new->enrollment_deadline = null;
            $new->save();
            foreach ($course->modules as $m) {
                $copy = $m->replicate();
                $copy->course_id = $new->id;
                $copy->save();
                foreach ($m->lessons as $l) {
                    $copyLesson = $l->replicate();
                    $copyLesson->module_id = $copy->id;
                    $copyLesson->published = false;
                    $copyLesson->save();
                }
            }

            return $new;
        });
        $this->audit($request, $new, 'course.duplicated');

        return redirect('/admin/courses/'.$new->id.'/edit');
    }

    public function preview(Request $request, Course $course): View
    {
        Gate::authorize('update', $course);
        $this->audit($request, $course, 'course.previewed');

        return view('public.preview', ['course' => $course->load('modules.lessons')]);
    }

    private function audit(Request $request, Course $course, string $action): void
    {
        DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'action' => $action, 'subject_type' => 'course', 'subject_id' => $course->id, 'reason' => $request->input('reason'), 'created_at' => now()]);
    }
}
