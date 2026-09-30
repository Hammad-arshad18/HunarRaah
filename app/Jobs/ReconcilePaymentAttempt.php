<?php

namespace App\Jobs;

use App\Actions\ReconcileOrderPayment;
use App\Models\PaymentAttempt;
use App\Services\StripeGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ReconcilePaymentAttempt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $attemptId) {}

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('stripe-reconciliation'))->shared()->releaseAfter(10)->expireAfter(180)];
    }

    public function handle(StripeGateway $gateway, ReconcileOrderPayment $action): void
    {
        $a = PaymentAttempt::findOrFail($this->attemptId);
        if ($a->session_id) {
            $action->execute($a, $gateway->state($a));
        }
    }
}
