<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use App\Services\Entitlement;

class CoursePolicy
{
    public function study(User $user, Course $course): bool
    {
        return app(Entitlement::class)->forCourse($user, $course) !== null;
    }

    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin' && ! $user->suspended_at && $user->two_factor_confirmed_at !== null;
    }
}
