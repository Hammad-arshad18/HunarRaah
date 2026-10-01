<?php

namespace App\Http\Controllers;

use App\Actions\CreateCourseCheckout;
use App\Jobs\ProcessStripeEvent;
use App\Models\Course;
use App\Models\Order;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('studio/purchases', ['orders' => Order::where('user_id', $request->user()->id)->select('public_reference', 'title_snapshot', 'payment_status', 'dispute_status', 'total_minor', 'refunded_minor', 'currency', 'created_at')->latest()->paginate(20)]);
    }

    public function checkout(Request $request, Course $course, CreateCourseCheckout $action): \Symfony\Component\HttpFoundation\Response
    {
        return Inertia::location($action->execute($request->user(), $course));
    }

    public function show(Request $request, string $reference): Response|JsonResponse
    {
        $order = Order::where('public_reference', $reference)->where('user_id', $request->user()->id)->firstOrFail();
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([...$order->only('public_reference', 'payment_status', 'dispute_status', 'refunded_minor'), 'has_access' => app(\App\Services\Entitlement::class)->forCourse($request->user(), $order->course) !== null])->header('Cache-Control', 'no-store');
        }

        return Inertia::render('studio/order', ['order' => [...$order->only('public_reference', 'payment_status', 'dispute_status', 'title_snapshot', 'total_minor', 'refunded_minor', 'currency', 'course_id'), 'has_access' => app(\App\Services\Entitlement::class)->forCourse($request->user(), $order->course) !== null]]);
    }

    public function webhook(Request $request): \Illuminate\Http\Response|JsonResponse
    {
        abort_unless(config('platform.stripe.webhook_secret'), 503);
        try {
            $event = Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), config('platform.stripe.webhook_secret'));
        } catch (\UnexpectedValueException|SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        $types = ['checkout.session.completed', 'checkout.session.expired', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed', 'charge.refunded', 'refund.created', 'refund.updated', 'charge.dispute.created', 'charge.dispute.updated', 'charge.dispute.closed'];
        if (! in_array($event->type, $types, true)) {
            return response()->noContent();
        }
        $o = $event->data->object;
        $payload = ['session_id' => str_starts_with($event->type, 'checkout.session.') ? $o->id : null, 'payment_intent_id' => $o->payment_intent ?? null, 'order_reference' => $o->metadata->order_reference ?? null];
        DB::transaction(function () use ($event, $payload) {
            $stored = WebhookEvent::firstOrCreate(['provider' => 'stripe', 'event_id' => $event->id], ['event_type' => $event->type, 'payload' => $payload, 'received_at' => now()]);
            if ($stored->processing_status !== 'processed') {
                ProcessStripeEvent::dispatch($stored->id)->afterCommit();
            }
        });

        return response()->noContent();
    }
}
