<?php

namespace App\Http\Controllers;

use App\Actions\CompleteLesson;
use App\Actions\GrantEnrollment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LiveSession;
use App\Models\Module;
use App\Services\Entitlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LearningController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $enrollments = Enrollment::where('user_id', $request->user()->id)->with('course')->get()->map(fn ($e) => ['id' => $e->id, 'status' => $e->status, 'completed_at' => $e->completed_at, 'course' => ['id' => $e->course->id, 'title' => $e->course->title, 'summary' => $e->course->summary, 'format' => $e->course->format]]);

        $certificates = Certificate::whereHas('enrollment', fn ($q) => $q->where('user_id', $request->user()->id))->whereNotNull('current_enrollment_id')->get()->map(fn ($c) => $c->only('credential_id', 'course_title', 'status'));
        $sessions = LiveSession::where('status', 'scheduled')->where('ends_at', '>', now())->whereHas('lesson.module.course.enrollments', fn ($q) => $q->where('user_id', $request->user()->id)->where('status', 'active'))->with('lesson.module')->orderBy('starts_at')->limit(5)->get()->map(fn ($s) => ['id' => $s->id, 'title' => $s->lesson->title, 'starts_at' => $s->starts_at, 'timezone' => $s->timezone, 'course_id' => $s->lesson->module->course_id, 'lesson_id' => $s->lesson_id]);

        return Inertia::render('studio/dashboard', ['enrollments' => $enrollments, 'certificates' => $certificates, 'sessions' => $sessions]);
    }

    public function enroll(Request $request, Course $course, GrantEnrollment $action): RedirectResponse
    {
        $action->free($request->user(), $course);

        return redirect('/learn/'.$course->id);
    }

    public function classroom(Request $request, Course $course, ?Lesson $lesson = null): Response|RedirectResponse
    {
        Gate::authorize('study', $course);
        $enrollment = app(Entitlement::class)->forCourse($request->user(), $course);
        $course->load(['modules.lessons' => fn ($q) => $q->where('published', true)]);
        $lessons = $course->modules->flatMap(fn ($m) => $m->lessons);
        $progress = $enrollment->progress()->get()->keyBy('lesson_id');
        $lesson ??= $lessons->first(fn ($l) => ! $progress->get($l->id)?->completed_at) ?? $lessons->first();
        abort_unless($lesson && $lessons->contains('id', $lesson->id), 404);
        if (! $request->route('lesson')) {
            return redirect('/learn/'.$course->id.'/'.$lesson->id);
        }
        $lesson->load('session');
        $required = $lessons->where('required', true);

        return Inertia::render('studio/classroom', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'enrollment' => ['id' => $enrollment->id, 'completed_at' => $enrollment->completed_at],
            'modules' => $course->modules->map(fn (Module $m): array => ['id' => $m->id, 'title' => $m->title, 'lessons' => $m->lessons->map(fn (Lesson $l): array => ['id' => $l->id, 'title' => $l->title, 'type' => $l->type, 'required' => $l->required, 'complete' => (bool) $progress->get($l->id)?->completed_at])]),
            'lesson' => ['id' => $lesson->id, 'title' => $lesson->title, 'type' => $lesson->type, 'html' => $lesson->type === 'text' ? Str::markdown($lesson->body ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) : null, 'video_status' => $lesson->video_status, 'position_seconds' => $progress->has($lesson->id) ? $progress->get($lesson->id)->position_seconds : 0, 'complete' => (bool) $progress->get($lesson->id)?->completed_at, 'session' => $lesson->session ? $lesson->session->only('id', 'starts_at', 'ends_at', 'timezone', 'status', 'message') : null],
            'progress' => ['completed' => $required->filter(fn ($l) => (bool) $progress->get($l->id)?->completed_at)->count(), 'required' => $required->count()],
        ]);
    }

    public function complete(Request $request, Lesson $lesson, CompleteLesson $action): RedirectResponse
    {
        $request->validate(['confirmed' => 'required|accepted']);
        $action->execute($request->user(), $lesson);

        return back();
    }

    public function position(Request $request, Lesson $lesson): \Illuminate\Http\Response
    {
        $data = $request->validate(['position_seconds' => 'required|integer|min:0|max:86400']);
        Gate::authorize('study', $lesson->module->course);
        abort_unless($lesson->published && $lesson->video_status === 'ready' && $lesson->duration_seconds, 409);
        $enrollment = app(Entitlement::class)->forCourse($request->user(), $lesson->module->course);
        DB::transaction(function () use ($enrollment, $lesson, $data) {
            Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            LessonProgress::updateOrCreate(['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id], ['position_seconds' => min($data['position_seconds'], $lesson->duration_seconds)]);
        });

        return response()->noContent();
    }
}
