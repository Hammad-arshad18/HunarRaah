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
use Inertia\Inertia;
use Inertia\Response;

class AdminCourseController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['status' => 'nullable|in:draft,published,archived']);

        return Inertia::render('studio/admin', ['courses' => Course::when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->orderByDesc('id')->paginate(20)->withQueryString(), 'filters' => $filters]);
    }

    public function edit(Course $course): Response
    {
        Gate::authorize('update', $course);

        return Inertia::render('studio/course-editor', ['course' => $course->load('modules.lessons'), 'locked' => $course->enrollments()->exists()]);
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

        return back();
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
            }if ($data['published'] && $data['type'] === 'video') {
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
        DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'action' => $action, 'subject_type' => 'course', 'subject_id' => $course->id, 'created_at' => now()]);
    }
}
