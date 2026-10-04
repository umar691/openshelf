<?php

namespace App\Services\Payments\Data;

final readonly class VerifiedWebhook
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public string $providerReference,
        public bool $isSuccessful,
        public ?int $amountMinor,
        public ?string $currency,
        public array $payload,
    ) {}
}
