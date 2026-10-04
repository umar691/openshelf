<?php

namespace App\Services\Payments;

use App\Models\EscrowContract;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Marketplace\InventoryService;
use App\Services\Marketplace\OrderFulfillmentService;
use App\Services\Payments\Data\CheckoutRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PaymentOrchestrator
{
    public function __construct(
        private readonly PaymentGatewayRegistry $gateways,
        private readonly InventoryService $inventory,
        private readonly OrderFulfillmentService $fulfillment,
    ) {}

    public function beginCheckout(
        string $gatewayName,
        Model $payable,
        int $userId,
        string $email,
        string $returnUrl,
        string $cancelUrl,
    ): array {
        [$amount, $currency, $ownerId, $description] = match (true) {
            $payable instanceof Order => [$payable->total_minor, $payable->currency, $payable->user_id, $payable->order_number],
            $payable instanceof EscrowContract => [$payable->total_minor - $payable->funded_minor, $payable->currency, $payable->client_id, $payable->title],
            default => throw new InvalidArgumentException('This record type cannot be charged through the platform checkout.'),
        };

        if ((int) $ownerId !== $userId || $amount < 1) {
            throw new InvalidArgumentException('The payable record is not owned by this customer or has an invalid amount.');
        }

        [$transaction, $amount, $currency] = DB::transaction(function () use ($payable, $userId, $gatewayName): array {
            $lockedPayable = $payable->newQuery()->lockForUpdate()->findOrFail($payable->getKey());

            if ($lockedPayable instanceof Order) {
                if ($lockedPayable->payment_status !== 'unpaid' || $lockedPayable->status !== 'pending') {
                    throw new InvalidArgumentException('This order is not awaiting payment.');
                }
                $amount = $lockedPayable->total_minor;
            } elseif (in_array($lockedPayable->status, ['funded', 'payment_review'], true)) {
                throw new InvalidArgumentException('This contract is not available for funding.');
            } else {
                $amount = $lockedPayable->total_minor - $lockedPayable->funded_minor;
            }

            if ($amount < 1) {
                throw new InvalidArgumentException('This record has no remaining balance to charge.');
            }

            if ($lockedPayable->paymentTransactions()->where('status', 'pending')->exists()) {
                throw new InvalidArgumentException('A payment attempt is already in progress for this record.');
            }

            $transaction = $lockedPayable->paymentTransactions()->create([
                'user_id' => $userId,
                'gateway' => $gatewayName,
                'idempotency_key' => (string) Str::uuid(),
                'amount_minor' => $amount,
                'currency' => $lockedPayable->currency,
                'status' => 'pending',
            ]);

            return [$transaction, $amount, $lockedPayable->currency];
        });

        $session = $this->gateways->for($gatewayName)->createCheckout(new CheckoutRequest(
            reference: (string) $transaction->id,
            amountMinor: $amount,
            currency: $currency,
            returnUrl: $returnUrl,
            cancelUrl: $cancelUrl,
            customerEmail: $email,
            metadata: [
                'payment_transaction_id' => (string) $transaction->id,
                'description' => $description,
            ],
        ));

        $transaction->update([
            'gateway_reference' => $session->providerReference,
            'gateway_metadata' => $session->metadata,
        ]);

        return ['transaction' => $transaction->fresh(), 'checkout' => $session];
    }

    public function processWebhook(string $gatewayName, Request $request): bool
    {
        $event = $this->gateways->for($gatewayName)->verifyWebhook($request);

        return DB::transaction(function () use ($gatewayName, $event): bool {
            $inserted = DB::table('payment_webhook_events')->insertOrIgnore([
                'gateway' => $gatewayName,
                'event_id' => $event->eventId,
                'event_type' => $event->eventType,
                'payload' => json_encode($event->payload, JSON_THROW_ON_ERROR),
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                return false;
            }

            $transaction = PaymentTransaction::query()
                ->where('gateway', $gatewayName)
                ->where('gateway_reference', $event->providerReference)
                ->lockForUpdate()
                ->firstOrFail();

            if ($event->amountMinor !== (int) $transaction->amount_minor || strtoupper((string) $event->currency) !== $transaction->currency) {
                throw new RuntimeException('The verified provider amount or currency does not match the pending payment.');
            }

            if ($transaction->status !== 'pending') {
                if ($event->isSuccessful && in_array($transaction->status, ['expired', 'failed'], true)) {
                    $transaction->update(['status' => 'paid_review', 'paid_at' => now()]);
                    $this->markPayableForReview($transaction);
                }

                return true;
            }

            if ($event->isSuccessful) {
                $payable = $transaction->payable()->lockForUpdate()->firstOrFail();
                if ($payable instanceof Order && ($payable->status !== 'pending' || $payable->payment_status !== 'unpaid')) {
                    $transaction->update(['status' => 'paid_review', 'paid_at' => now()]);
                    $payable->update(['status' => 'payment_review', 'payment_status' => 'paid_review']);
                } else {
                    $this->markPayablePaid($payable, $transaction);
                    $transaction->update(['status' => 'paid', 'paid_at' => now()]);
                }
            } elseif (in_array($event->eventType, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
                $transaction->update(['status' => 'failed']);
                if ($transaction->payable instanceof Order) {
                    $transaction->payable->update(['status' => 'cancelled', 'payment_status' => 'failed']);
                    $this->inventory->releaseForFailedOrder($transaction->payable);
                }
            }

            return true;
        });
    }

    private function markPayablePaid(Model $payable, PaymentTransaction $transaction): void
    {
        if ($payable instanceof Order) {
            $payable->update(['status' => 'paid', 'payment_status' => 'paid', 'fulfillment_status' => 'processing']);
            $this->fulfillment->fulfillPaidOrder($payable);

            return;
        }

        if ($payable instanceof EscrowContract) {
            $fundedAmount = $payable->funded_minor + $transaction->amount_minor;
            if ($fundedAmount > $payable->total_minor) {
                throw new RuntimeException('The verified payment exceeds the remaining contract balance.');
            }

            $payable->update([
                'funded_minor' => $fundedAmount,
                'status' => $fundedAmount === $payable->total_minor ? 'funded' : 'partially_funded',
                'funded_at' => $fundedAmount === $payable->total_minor ? now() : $payable->funded_at,
            ]);

            return;
        }

        throw new InvalidArgumentException('Unsupported payable type.');
    }

    private function markPayableForReview(PaymentTransaction $transaction): void
    {
        $payable = $transaction->payable()->lockForUpdate()->firstOrFail();

        if ($payable instanceof Order) {
            $payable->update(['status' => 'payment_review', 'payment_status' => 'paid_review']);
        } elseif ($payable instanceof EscrowContract) {
            $payable->update(['status' => 'payment_review']);
        }
    }
}
