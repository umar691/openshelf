<?php

namespace App\Services\Payments\Data;

final readonly class CheckoutSession
{
    public function __construct(
        public string $providerReference,
        public ?string $redirectUrl,
        public array $metadata = [],
    ) {}
}
