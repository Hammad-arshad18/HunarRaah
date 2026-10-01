<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string $view = 'filament.dashboard';

    public function getTitle(): string
    {
        return 'Your teaching desk';
    }

    /** @return array<string,int> */
    public function counts(): array
    {
        return ['Published courses' => Course::where('status', 'published')->count(), 'Students' => User::where('role', 'student')->count(), 'Active enrollments' => Enrollment::where('status', 'active')->count(), 'Pending payments' => Order::where('payment_status', 'pending')->count()];
    }
}
