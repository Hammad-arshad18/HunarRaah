<?php

namespace App\Actions;

use App\Jobs\GenerateCertificate;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Entitlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueCertificate
{
    public function execute(User $user, Enrollment $enrollment): Certificate
    {
        abort_unless($enrollment->user_id === $user->id, 404);

        return DB::transaction(function () use ($user, $enrollment) {
            $enrollment = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            $course = $enrollment->course;
            abort_unless(app(Entitlement::class)->forCourse($user, $course) && $course->certificate_enabled && $enrollment->completed_at, 409, 'Certificate not eligible.');
            $required = $course->lessons()->where('required', true)->pluck('lessons.id');
            abort_unless($required->count() > 0 && $enrollment->progress()->whereIn('lesson_id', $required)->whereNotNull('completed_at')->count() === $required->count(), 409);
            $c = Certificate::firstOrCreate(['current_enrollment_id' => $enrollment->id], ['enrollment_id' => $enrollment->id, 'credential_id' => Str::uuid()->toString(), 'verification_token' => bin2hex(random_bytes(32)), 'learner_name' => $user->name, 'course_title' => $course->title, 'issuer_name' => config('platform.issuer'), 'signatory' => config('platform.signatory'), 'issued_at' => now(), 'issued_timezone' => config('platform.timezone')]);
            if ($c->generation_status !== 'ready') {
                GenerateCertificate::dispatch($c->id)->afterCommit();
            }

            return $c;
        });
    }
}
