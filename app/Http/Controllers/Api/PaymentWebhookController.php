<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentOrchestrator $payments): JsonResponse
    {
        abort_unless(is_string(config("payments.gateways.{$gateway}.driver")), 404);

        $processed = $payments->processWebhook($gateway, $request);

        return response()->json(['accepted' => true, 'processed' => $processed]);
    }
}
