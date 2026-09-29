<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Mail;

class SendTransactionalMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $deliveryId) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('mail-'.$this->deliveryId))->releaseAfter(10)->expireAfter(180)];
    }

    public function handle(): void
    {
        $d = NotificationDelivery::findOrFail($this->deliveryId);
        if ($d->status === 'sent') {
            return;
        }
        $message = match ($d->type) {
            'enrollment' => 'Your course access is ready.','certificate' => 'Your certificate is ready.','session-change' => 'Your live session schedule has changed.','session-reminder' => 'Your live session starts in approximately 24 hours.','access-change' => 'Your course access status has changed.',default => 'There is an update to your learning account.'
        };
        try {
            Mail::raw($message."\nSign in to see the current details: ".url('/dashboard'), fn ($mail) => $mail->to($d->user->email)->subject($message));
            $d->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $d->update(['status' => 'failed']);
            throw $e;
        }
    }
}
