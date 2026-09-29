<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Services\StripeGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCourseCheckout
{
    public function execute(User $user, Course $course): string
    {
        abort_unless($user->hasVerifiedEmail() && ! $user->suspended_at, 403);
        abort_unless(config('platform.stripe.secret'), 503, 'Payments are not configured.');
        $order = DB::transaction(function () use ($user, $course) {
            $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_unless($course->acceptsEnrollment() && $course->price_minor > 0 && $course->currency === config('platform.currency'), 409, 'Checkout unavailable.');
            $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
            abort_if($enrollment && ($enrollment->status === 'active' || $enrollment->admin_restriction), 409, 'You already have access or must contact support.');
            $pending = Order::where('user_id', $user->id)->where('course_id', $course->id)->where('payment_status', 'pending')->latest('id')->first();
            if ($pending) {
                return $pending;
            }
            $order = Order::create(['public_reference' => Str::uuid()->toString(), 'user_id' => $user->id, 'course_id' => $course->id, 'title_snapshot' => $course->title, 'learner_name' => $user->name, 'terms_version' => config('platform.terms_version'), 'currency' => $course->currency, 'subtotal_minor' => $course->price_minor, 'tax_minor' => 0, 'total_minor' => $course->price_minor]);
            $order->attempt()->create(['idempotency_key' => 'course-checkout-'.$order->public_reference]);

            return $order;
        });
        $attempt = $order->attempt;
        abort_if(! $attempt->session_id && $attempt->created_at->lt(now()->subHours(23)), 503, 'Checkout creation is unresolved. Contact support before retrying.');
        if ($attempt->session_id) {
            $session = app(StripeGateway::class)->state($attempt);
            if (($session['status'] ?? null) === 'expired') {
                $order->update(['payment_status' => 'expired']);
                abort(409, 'The quote expired. Return to the course page and retry.');
            }
        } else {
            $session = app(StripeGateway::class)->create($order, $attempt);
            $attempt->update(['session_id' => $session['id'], 'status' => $session['status'], 'expires_at' => isset($session['expires_at']) ? Carbon::createFromTimestamp($session['expires_at']) : null]);
        }
        abort_unless(($session['status'] ?? null) === 'open' && ! empty($session['url']) && str_starts_with($session['url'], 'https://checkout.stripe.com/'), 409, 'Checkout expired or awaiting confirmation. See your order.');

        return $session['url'];
    }
}
