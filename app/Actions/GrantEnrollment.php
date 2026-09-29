<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\TransactionalMail;
use Illuminate\Support\Facades\DB;

class GrantEnrollment
{
    public function free(User $user, Course $course): Enrollment
    {
        abort_unless($user->hasVerifiedEmail() && ! $user->suspended_at, 403);

        return DB::transaction(function () use ($user, $course) {
            $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_unless($course->acceptsEnrollment() && $course->price_minor === 0, 409, 'Free enrollment is unavailable.');
            $existing = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
            if ($existing) {
                abort_unless($existing->status === 'active' && ! $existing->admin_restriction, 409, 'Contact support about your access.');

                return $existing;
            }
            $enrollment = Enrollment::create(['user_id' => $user->id, 'course_id' => $course->id, 'source' => 'free', 'status' => 'active', 'granted_at' => now(), 'terms_version' => config('platform.terms_version')]);
            app(TransactionalMail::class)->queue($user->id, 'enrollment', (string) $enrollment->id);

            return $enrollment;
        });
    }
}
