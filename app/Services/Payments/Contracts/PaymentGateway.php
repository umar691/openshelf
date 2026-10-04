<?php

namespace App\Services\Payments\Contracts;

use App\Services\Payments\Data\CheckoutRequest;
use App\Services\Payments\Data\CheckoutSession;
use App\Services\Payments\Data\VerifiedWebhook;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function createCheckout(CheckoutRequest $request): CheckoutSession;

    public function verifyWebhook(Request $request): VerifiedWebhook;
}
