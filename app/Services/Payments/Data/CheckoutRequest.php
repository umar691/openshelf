<?php

namespace App\Services\Payments\Data;

final readonly class CheckoutRequest
{
    public function __construct(
        public string $reference,
        public int $amountMinor,
        public string $currency,
        public string $returnUrl,
        public string $cancelUrl,
        public string $customerEmail,
        public array $metadata = [],
    ) {}
}
