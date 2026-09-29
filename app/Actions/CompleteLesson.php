<?php

namespace App\Actions;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\Entitlement;
use Illuminate\Support\Facades\DB;

class CompleteLesson
{
    public function execute(User $user, Lesson $lesson, string $source = 'self_attested', ?string $reason = null, ?Enrollment $target = null): void
    {
        $course = $lesson->module->course;
        $enrollment = $target ?: app(Entitlement::class)->forCourse($user, $course);
        abort_unless($enrollment && $enrollment->course_id === $course->id && $lesson->published, 403);
        if ($source !== 'self_attested') {
            abort_unless($user->role === 'admin' && $user->two_factor_confirmed_at && $reason, 403);
        }
        DB::transaction(function () use ($enrollment, $lesson, $course, $source, $user, $reason) {
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if ($source === 'self_attested') {
                abort_unless(app(Entitlement::class)->forCourse($user, $course) !== null, 403);
                $progress = LessonProgress::where('enrollment_id', $locked->id)->where('lesson_id', $lesson->id)->first();
                if ($lesson->type !== 'text') {
                    abort_unless($lesson->video_status === 'ready' && ($progress ? $progress->position_seconds : 0) > 0, 409, 'Begin the recording before confirming completion.');
                    abort_unless($lesson->type !== 'live' || $course->recording_alternative, 409, 'Administrator attendance is required.');
                }
            }
            $progress = LessonProgress::firstOrCreate(['enrollment_id' => $locked->id, 'lesson_id' => $lesson->id]);
            if (! $progress->completed_at) {
                $progress->update(['completed_at' => now(), 'completion_source' => $source, 'actor_id' => $user->id]);
                if ($source !== 'self_attested') {
                    DB::table('audit_logs')->insert(['actor_id' => $user->id, 'action' => 'lesson.completed', 'subject_type' => 'enrollment', 'subject_id' => $locked->id, 'reason' => $reason, 'created_at' => now()]);
                }
            }
            $required = $course->lessons()->where('required', true)->pluck('lessons.id');
            $completed = LessonProgress::where('enrollment_id', $locked->id)->whereIn('lesson_id', $required)->whereNotNull('completed_at')->count();
            if ($required->count() > 0 && $completed === $required->count() && ! $locked->completed_at) {
                $locked->update(['completed_at' => now()]);
            }
        });
    }
}
