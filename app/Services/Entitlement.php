<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class Entitlement
{
    public function forCourse(User $user, Course $course): ?Enrollment
    {
        if ($user->suspended_at || ! $user->hasVerifiedEmail() || $course->takedown_reason) {
            return null;
        }

        return Enrollment::where('user_id', $user->id)->where('course_id', $course->id)
            ->where('status', 'active')->whereNull('admin_restriction')
            ->where(fn ($q) => $q->whereNull('access_ends_at')->orWhere('access_ends_at', '>', now()))->first();
    }
}
