<?php

namespace App\Console\Commands;

use App\Jobs\ProcessStripeEvent;
use App\Jobs\ReconcilePaymentAttempt;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LiveSession;
use App\Models\PaymentAttempt;
use App\Models\WebhookEvent;
use App\Services\StreamGateway;
use App\Services\TransactionalMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MaintainPlatform extends Command
{
    protected $signature = 'platform:maintain';

    protected $description = 'Recover pending webhooks, reconcile payments/videos and schedule live reminders';

    public function handle(): int
    {
        WebhookEvent::whereIn('processing_status', ['pending', 'failed'])->where('attempts', '<', 10)->limit(100)->get()->each(fn ($e) => ProcessStripeEvent::dispatch($e->id));
        if (config('platform.stripe.secret')) {
            PaymentAttempt::whereNotNull('session_id')->where(fn ($q) => $q->whereNull('last_reconciled_at')->orWhere('last_reconciled_at', '<', now()->subHour()))->where('updated_at', '>', now()->subDays(30))->limit(100)->get()->each(fn ($a) => ReconcilePaymentAttempt::dispatch($a->id));
        }
        if (config('platform.stream.token')) {
            foreach (Lesson::whereNotNull('video_uid')->whereIn('video_status', ['pending', 'processing'])->limit(50)->get() as $lesson) {
                try {
                    $v = app(StreamGateway::class)->inspect($lesson->video_uid);
                    $lesson->update(['video_status' => ($v['readyToStream'] ?? false) ? 'ready' : (($v['status']['state'] ?? '') === 'error' ? 'failed' : 'processing'), 'duration_seconds' => isset($v['duration']) ? (int) ceil($v['duration']) : null]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }
        foreach (LiveSession::where('status', 'scheduled')->whereBetween('starts_at', [now()->addHours(24), now()->addHours(24)->addMinutes(5)])->where('created_at', '<', now())->with('lesson.module')->get() as $session) {
            Enrollment::where('course_id', $session->lesson->module->course_id)->where('status', 'active')->each(fn ($e) => app(TransactionalMail::class)->queue($e->user_id, 'session-reminder', $session->id.':'.$session->revision));
        }
        Cache::put('platform.scheduler_heartbeat', now()->toIso8601String(), now()->addMinutes(15));

        return self::SUCCESS;
    }
}
