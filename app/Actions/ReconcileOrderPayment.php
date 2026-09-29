<?php

namespace App\Actions;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\TransactionalMail;
use Illuminate\Support\Facades\DB;

class ReconcileOrderPayment
{
    /** @param array<string,mixed> $state */
    public function execute(PaymentAttempt $attempt, array $state): void
    {
        $order = $attempt->order;
        $live = str_starts_with(config('platform.stripe.secret', ''), 'sk_live_');
        if (($state['id'] ?? null) !== $attempt->session_id || ($state['metadata']['order_reference'] ?? null) !== $order->public_reference || ($state['client_reference_id'] ?? null) !== $order->public_reference || ($state['amount_total'] ?? null) !== $order->total_minor || strtoupper($state['currency'] ?? '') !== $order->currency || (bool) ($state['livemode'] ?? false) !== $live) {
            throw new \RuntimeException('payment_state_mismatch');
        }
        DB::transaction(function () use ($order, $attempt, $state) {
            Course::whereKey($order->course_id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $refunded = max($order->refunded_minor, (int) ($state['refunded_minor'] ?? 0));
            $dispute = $state['dispute_status'] ?? 'none';
            if ($order->dispute_status === 'lost') {
                $dispute = 'lost';
            }
            if ($order->dispute_status === 'open' && $dispute === 'none') {
                $dispute = 'open';
            }
            $paid = ($state['payment_status'] ?? '') === 'paid';
            $status = $refunded >= $order->total_minor ? 'refunded' : ($paid ? 'paid' : (($state['status'] ?? '') === 'expired' ? 'expired' : 'pending'));
            if ($order->payment_status === 'paid' && ! $paid) {
                $status = 'paid';
            }
            $order->update(['payment_status' => $status, 'refunded_minor' => $refunded, 'dispute_status' => $dispute]);
            if (! $paid && ! empty($state['failed']) && $status === 'pending') {
                $order->update(['payment_status' => 'failed']);
            }
            foreach ($state['refunds'] ?? [] as $refund) {
                abort_unless(strtoupper($refund['currency']) === $order->currency, 409, 'Refund currency mismatch.');
                DB::table('refunds')->updateOrInsert(['provider_refund_id' => $refund['id']], ['order_id' => $order->id, 'amount_minor' => $refund['amount'], 'currency' => $order->currency, 'status' => $refund['status'], 'processed_at' => now(), 'updated_at' => now(), 'created_at' => now()]);
            }
            $attempt->update(['status' => $state['status'], 'payment_intent_id' => $state['payment_intent_id'] ?? $attempt->payment_intent_id, 'last_reconciled_at' => now()]);
            $enrollment = Enrollment::where('user_id', $order->user_id)->where('course_id', $order->course_id)->lockForUpdate()->first();
            if ($paid && ! $enrollment) {
                $enrollment = Enrollment::create(['user_id' => $order->user_id, 'course_id' => $order->course_id, 'current_order_id' => $order->id, 'source' => 'paid', 'status' => 'active', 'granted_at' => now(), 'terms_version' => $order->terms_version]);
            }
            if ($paid && $enrollment && $enrollment->current_order_id !== $order->id) {
                if ($enrollment->status === 'revoked' && ! $enrollment->admin_restriction) {
                    $enrollment->update(['current_order_id' => $order->id, 'source' => 'paid']);
                } else {
                    $order->update(['financial_exception' => true]);

                    return;
                }
            }
            if (! $enrollment || $enrollment->current_order_id !== $order->id) {
                return;
            }
            if ($paid) {
                app(TransactionalMail::class)->queue($order->user_id, 'enrollment', (string) $enrollment->id);
            }
            $access = ($status === 'refunded' || $dispute === 'lost') ? 'revoked' : ($dispute === 'open' || $enrollment->admin_restriction ? 'suspended' : 'active');
            $enrollment->update(['status' => $access, 'restriction_reason' => $access === 'active' ? null : ($enrollment->admin_restriction ?: ($dispute === 'open' ? 'Payment dispute pending' : 'Payment refunded or disputed'))]);
            $credentials = Certificate::where('enrollment_id', $enrollment->id)->whereIn('status', ['valid', 'suspended']);
            if ($access === 'revoked') {
                $credentials->update(['status' => 'revoked', 'revoked_at' => now()]);
            } elseif ($dispute === 'open') {
                $credentials->update(['status' => 'suspended']);
            } elseif ($dispute === 'won') {
                $credentials->where('status', 'suspended')->update(['status' => 'valid']);
            }
            DB::table('audit_logs')->insert(['action' => 'payment.reconciled', 'subject_type' => 'order', 'subject_id' => $order->id, 'created_at' => now()]);
        });
    }
}
