<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentAttempt;
use Stripe\StripeClient;

class StripeGateway
{
    private function client(): StripeClient
    {
        $key = config('platform.stripe.secret');
        abort_unless($key, 503, 'Payments are not configured.');
        if (str_starts_with($key, 'sk_live_')) {
            abort_unless(config('platform.stripe.live_enabled') && BusinessPolicies::ready() && config('platform.merchant_country') && config('platform.tax_policy') === 'inclusive-no-separate-tax', 503, 'Live payments are disabled. Review merchant, policies and tax presentation.');
        }

        return new StripeClient(['api_key' => $key, 'stripe_version' => '2025-09-30.clover']);
    }

    /** @return array<string, mixed> */
    public function create(Order $order, PaymentAttempt $attempt): array
    {
        return $this->client()->checkout->sessions->create([
            'mode' => 'payment', 'payment_method_types' => ['card'], 'client_reference_id' => $order->public_reference,
            'metadata' => ['order_reference' => $order->public_reference], 'payment_intent_data' => ['metadata' => ['order_reference' => $order->public_reference]],
            'line_items' => [['quantity' => 1, 'price_data' => ['currency' => strtolower($order->currency), 'unit_amount' => $order->total_minor, 'product_data' => ['name' => $order->title_snapshot]]]],
            'success_url' => url('/orders/'.$order->public_reference), 'cancel_url' => url('/orders/'.$order->public_reference),
        ], ['idempotency_key' => $attempt->idempotency_key])->toArray();
    }

    /** @return array<string, mixed> */
    public function state(PaymentAttempt $attempt): array
    {
        $session = $this->client()->checkout->sessions->retrieve($attempt->session_id, ['expand' => ['payment_intent.latest_charge']])->toArray();
        $intent = $session['payment_intent'] ?? null;
        $charge = is_array($intent) ? ($intent['latest_charge'] ?? null) : null;
        $session['refunded_minor'] = is_array($charge) ? ($charge['amount_refunded'] ?? 0) : 0;
        $session['dispute_status'] = 'none';
        if (is_array($charge)) {
            $disputes = $this->client()->disputes->all(['payment_intent' => $intent['id'], 'limit' => 100]);
            $states = array_map(fn ($d) => $d->status, $disputes->data);
            $session['dispute_status'] = ! $states ? 'none' : (in_array('lost', $states, true) ? 'lost' : (count(array_diff($states, ['won', 'warning_closed'])) ? 'open' : 'won'));
            $session['refunds'] = $this->client()->refunds->all(['charge' => $charge['id'], 'limit' => 100])->toArray()['data'];
        }
        $session['failed'] = is_array($intent) && ! empty($intent['last_payment_error']) && ($intent['status'] ?? '') === 'requires_payment_method';
        $session['payment_intent_id'] = is_array($intent) ? $intent['id'] : $intent;

        return $session;
    }
}
