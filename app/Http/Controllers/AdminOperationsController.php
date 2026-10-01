<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateCertificate;
use App\Jobs\ReconcilePaymentAttempt;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Services\TransactionalMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminOperationsController extends Controller
{
    public function email(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'student', 403);
        $data = $request->validate(['email' => ['required', 'email:rfc', 'not_regex:/[\x00-\x20\x7f]/', 'max:255', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)], 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $user, $data) {
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $u->forceFill(['email' => strtolower($data['email']), 'email_verified_at' => null, 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $u->id)->delete();
            $this->audit($request, 'user.email_corrected', 'user', $u->id, $data['reason']);
            DB::afterCommit(fn () => $u->sendEmailVerificationNotification());
        });

        return back()->with('toast', ['type' => 'success', 'message' => 'Email corrected. The learner must verify the new address.']);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['suspended' => 'required|boolean', 'reason' => 'required|string|max:1000']);
        abort_unless($user->role === 'student', 403);
        DB::transaction(function () use ($request, $user, $data) {
            $user->forceFill(['suspended_at' => $data['suspended'] ? now() : null])->save();
            $user->forceFill(['remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $this->audit($request, 'user.suspension', 'user', $user->id, $data['reason']);
        });

        return back();
    }

    public function grant(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate(['user_id' => 'required|exists:users,id', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $course, $data) {
            $locked = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $user = User::whereKey((int) $data['user_id'])->firstOrFail();
            abort_unless($user->hasVerifiedEmail() && ! $user->suspended_at, 409, 'Student must be active and verified.');
            $e = Enrollment::firstOrCreate(['course_id' => $locked->id, 'user_id' => $user->id], ['source' => 'complimentary', 'status' => 'active', 'granted_at' => now(), 'terms_version' => config('platform.terms_version')]);
            abort_unless($e->source === 'complimentary' && ! $e->admin_restriction, 409, 'Existing paid/free entitlement requires explicit review.');
            $this->audit($request, 'enrollment.complimentary', 'enrollment', $e->id, $data['reason']);
            app(TransactionalMail::class)->queue($user->id, 'enrollment', (string) $e->id);
        });

        return back();
    }

    public function restrict(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:active,suspended,revoked', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $enrollment, $data) {
            $e = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if ($data['status'] === 'active' && $e->source === 'paid') {
                $order = Order::find($e->current_order_id);
                abort_unless($order && $order->payment_status === 'paid' && $order->refunded_minor < $order->total_minor && in_array($order->dispute_status, ['none', 'won'], true), 409, 'Payment restriction still applies.');
            }$e->update(['status' => $data['status'], 'admin_restriction' => $data['status'] === 'active' ? null : $data['reason'], 'restriction_reason' => $data['status'] === 'active' ? null : $data['reason']]);
            $this->audit($request, 'enrollment.restricted', 'enrollment', $e->id, $data['reason']);
        });

        return back();
    }

    public function reconcile(Request $request, Order $order): RedirectResponse
    {
        abort_unless((bool) $order->attempt?->session_id, 409, 'Checkout session has not been created.');
        ReconcilePaymentAttempt::dispatch($order->attempt->id);
        $this->audit($request, 'payment.retry', 'order', $order->id, 'Administrator reconciliation retry');

        return back();
    }

    public function revoke(Request $request, Certificate $certificate): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:1000']);
        $certificate->update(['status' => 'revoked', 'revoked_at' => now()]);
        $this->audit($request, 'certificate.revoked', 'certificate', $certificate->id, $data['reason']);

        return back();
    }

    public function reissue(Request $request, Certificate $certificate): RedirectResponse
    {
        $data = $request->validate(['learner_name' => 'required|string|max:255', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $certificate, $data) {
            $old = Certificate::whereKey($certificate->id)->lockForUpdate()->firstOrFail();
            abort_unless($old->current_enrollment_id && $old->status === 'valid', 409);
            $enrollmentId = $old->current_enrollment_id;
            $old->update(['status' => 'superseded', 'current_enrollment_id' => null]);
            $new = Certificate::create(['enrollment_id' => $old->enrollment_id, 'current_enrollment_id' => $enrollmentId, 'credential_id' => Str::uuid()->toString(), 'verification_token' => bin2hex(random_bytes(32)), 'learner_name' => $data['learner_name'], 'course_title' => $old->course_title, 'issuer_name' => $old->issuer_name, 'signatory' => $old->signatory, 'issued_at' => $old->issued_at, 'issued_timezone' => $old->issued_timezone, 'supersedes_id' => $old->id]);
            GenerateCertificate::dispatch($new->id)->afterCommit();
            $this->audit($request, 'certificate.reissued', 'certificate', $new->id, $data['reason']);
        });

        return back();
    }

    private function audit(Request $request, string $action, string $type, int $id, string $reason): void
    {
        DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'action' => $action, 'subject_type' => $type, 'subject_id' => $id, 'reason' => $reason, 'created_at' => now()]);
    }
}
