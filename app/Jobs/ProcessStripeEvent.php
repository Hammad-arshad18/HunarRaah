<?php

namespace App\Jobs;

use App\Actions\ReconcileOrderPayment;
use App\Models\PaymentAttempt;
use App\Models\WebhookEvent;
use App\Services\StripeGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ProcessStripeEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $eventId) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 120, 300];
    }

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('stripe-reconciliation'))->shared()->releaseAfter(10)->expireAfter(180)];
    }

    public function handle(StripeGateway $gateway, ReconcileOrderPayment $reconcile): void
    {
        $event = WebhookEvent::findOrFail($this->eventId);
        if ($event->processing_status === 'processed') {
            return;
        } $event->increment('attempts');
        try {
            $p = $event->payload;
            $attempt = ! empty($p['session_id']) ? PaymentAttempt::where('session_id', $p['session_id'])->first() : PaymentAttempt::where('payment_intent_id', $p['payment_intent_id'] ?? '')->first();
            if (! $attempt && ! empty($p['order_reference'])) {
                $attempt = PaymentAttempt::whereHas('order', fn ($q) => $q->where('public_reference', $p['order_reference']))->first();
            }
            if (! $attempt?->session_id) {
                throw new \RuntimeException('unknown_payment_attempt');
            }
            $reconcile->execute($attempt, $gateway->state($attempt));
            $event->update(['processing_status' => 'processed', 'processed_at' => now(), 'error_code' => null]);
        } catch (\Throwable $e) {
            $event->update(['processing_status' => 'failed', 'error_code' => 'reconciliation_failed']);
            throw $e;
        }
    }
}
