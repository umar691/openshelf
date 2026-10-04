<?php

namespace App\Services\Payments\Gateways;

use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Data\CheckoutRequest;
use App\Services\Payments\Data\CheckoutSession;
use App\Services\Payments\Data\VerifiedWebhook;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGateway
{
    public function createCheckout(CheckoutRequest $request): CheckoutSession
    {
        $secret = config('payments.gateways.stripe.secret');
        if (! is_string($secret) || $secret === '') {
            throw new InvalidArgumentException('Stripe is not configured. Set STRIPE_SECRET before creating a checkout.');
        }

        $session = (new StripeClient($secret))->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $request->customerEmail,
            'client_reference_id' => $request->reference,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($request->currency),
                    'unit_amount' => $request->amountMinor,
                    'product_data' => ['name' => $request->metadata['description'] ?? $request->reference],
                ],
            ]],
            'metadata' => array_map('strval', $request->metadata),
            'success_url' => $request->returnUrl,
            'cancel_url' => $request->cancelUrl,
        ]);

        return new CheckoutSession($session->id, $session->url, ['mode' => $session->mode]);
    }

    public function verifyWebhook(Request $request): VerifiedWebhook
    {
        $secret = config('payments.gateways.stripe.webhook_secret');
        if (! is_string($secret) || $secret === '') {
            throw new InvalidArgumentException('Stripe webhook verification is not configured.');
        }

        $event = Webhook::constructEvent(
            $request->getContent(),
            (string) $request->header('Stripe-Signature'),
            $secret,
        );
        $object = $event->data->object;
        $paymentStatus = $object->payment_status ?? null;
        $reference = $object->id ?? null;
        $amount = $object->amount_total ?? null;
        $currency = $object->currency ?? null;

        if (! is_string($reference) || $reference === '' || ! is_numeric($amount) || ! is_string($currency)) {
            throw new InvalidArgumentException('Stripe webhook is missing its checkout session reference, amount, or currency.');
        }

        return new VerifiedWebhook(
            $event->id,
            $event->type,
            $reference,
            $event->type === 'checkout.session.completed' && $paymentStatus === 'paid',
            (int) $amount,
            strtoupper($currency),
            ['provider' => 'stripe', 'event_id' => $event->id, 'event_type' => $event->type, 'session_id' => $reference],
        );
    }
}
