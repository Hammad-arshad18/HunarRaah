<?php

namespace App\Http\Controllers;

use App\Actions\CompleteLesson;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LiveSession;
use App\Services\StreamGateway;
use App\Services\TransactionalMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function roster(Lesson $lesson): Response
    {
        Gate::authorize('update', $lesson->module->course);

        return Inertia::render('studio/roster', ['lesson' => $lesson->only('id', 'title'), 'enrollments' => Enrollment::where('course_id', $lesson->module->course_id)->where('status', 'active')->with('user:id,name')->paginate(20)]);
    }

    public function playback(Request $request, Lesson $lesson, StreamGateway $stream): JsonResponse
    {
        Gate::authorize('study', $lesson->module->course);
        abort_unless($lesson->published && $lesson->video_status === 'ready' && $lesson->video_uid, 409, 'Recording processing or unavailable.');
        $token = $stream->token($lesson->video_uid);

        return response()->json(['url' => 'https://iframe.videodelivery.net/'.$token, 'expires_at' => now()->addMinutes(10)->toIso8601String()])->header('Cache-Control', 'no-store, private');
    }

    public function attach(Request $request, Lesson $lesson, StreamGateway $stream): RedirectResponse
    {
        Gate::authorize('update', $lesson->module->course);
        $data = $request->validate(['video_uid' => 'required|regex:/^[a-f0-9]{32}$/', 'reason' => 'required|string|max:1000']);
        $video = $stream->inspect($data['video_uid']);
        abort_unless(($video['uid'] ?? null) === $data['video_uid'] && ($video['requireSignedURLs'] ?? false) && ! empty($video['allowedOrigins']), 422, 'Use a private owned recording with restricted origins.');
        $state = ($video['readyToStream'] ?? false) ? 'ready' : (($video['status']['state'] ?? '') === 'error' ? 'failed' : 'processing');
        $lesson->update(['video_uid' => $data['video_uid'], 'video_status' => $state, 'duration_seconds' => isset($video['duration']) ? (int) ceil($video['duration']) : null]);
        $this->audit($request, 'video.attached', $lesson->id, $data['reason']);

        return back();
    }

    public function join(Request $request, LiveSession $session): \Symfony\Component\HttpFoundation\Response
    {
        Gate::authorize('study', $session->lesson->module->course);
        abort_unless($session->lesson->published && $session->status === 'scheduled', 409, 'This session is cancelled or ended.');
        abort_unless(now()->between($session->starts_at->subMinutes(15), $session->ends_at->addMinutes(30)), 409, 'Joining opens 15 minutes before the session and closes 30 minutes after it ends.');
        $this->validateMeetingUrl($session->join_url);

        return Inertia::location($session->join_url);
    }

    public function schedule(Request $request, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $lesson->module->course);
        abort_unless($lesson->type === 'live', 422);
        $data = $request->validate(['provider' => 'required|string|max:80', 'join_url' => 'required|url:https|max:2000', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at', 'timezone' => 'required|timezone', 'status' => 'required|in:scheduled,cancelled,completed', 'message' => 'nullable|string|max:1000', 'reason' => 'required|string|max:1000']);
        $this->validateMeetingUrl($data['join_url']);
        $reason = $data['reason'];
        unset($data['reason']);
        $session = $lesson->session()->updateOrCreate(['lesson_id' => $lesson->id], $data);
        $session->increment('revision');
        Enrollment::where('course_id', $lesson->module->course_id)->where('status', 'active')->each(fn ($e) => app(TransactionalMail::class)->queue($e->user_id, 'session-change', $session->id.':'.$session->revision));
        $this->audit($request, 'session.changed', $lesson->id, $reason);

        return back();
    }

    public function attendance(Request $request, Lesson $lesson, Enrollment $enrollment, CompleteLesson $action): RedirectResponse
    {
        Gate::authorize('update', $lesson->module->course);
        abort_unless($lesson->type === 'live' && $enrollment->course_id === $lesson->module->course_id, 404);
        $data = $request->validate(['reason' => 'required|string|max:1000']);
        $action->execute($request->user(), $lesson, 'attendance', $data['reason'], $enrollment);

        return back();
    }

    public function validateMeetingUrl(string $url): void
    {
        $p = parse_url($url);
        abort_unless($p && ($p['scheme'] ?? '') === 'https' && ! isset($p['user']) && ! isset($p['pass']) && (! isset($p['port']) || $p['port'] === 443) && in_array(strtolower($p['host'] ?? ''), config('platform.meeting_hosts'), true), 422, 'Meeting host is not approved.');
    }

    private function audit(Request $request, string $action, int $id, string $reason): void
    {
        DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'action' => $action, 'subject_type' => 'lesson', 'subject_id' => $id, 'reason' => $reason, 'created_at' => now()]);
    }
}
