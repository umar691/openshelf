<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayRegistry
{
    public function for(string $gateway): PaymentGateway
    {
        $driver = config("payments.gateways.{$gateway}.driver");

        if (! is_string($driver) || ! is_a($driver, PaymentGateway::class, true)) {
            throw new InvalidArgumentException("Payment gateway [{$gateway}] is not configured.");
        }

        return app($driver);
    }
}
