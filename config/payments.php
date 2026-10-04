<?php

return [
    'default' => env('PAYMENT_GATEWAY'),

    'gateways' => [
        'stripe' => [
            'driver' => App\Services\Payments\Gateways\StripePaymentGateway::class,
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'easypaisa' => [
            'driver' => env('EASYPAISA_GATEWAY_DRIVER'),
            'merchant_id' => env('EASYPAISA_MERCHANT_ID'),
            'store_id' => env('EASYPAISA_STORE_ID'),
            'secret' => env('EASYPAISA_SECRET'),
            'base_url' => env('EASYPAISA_BASE_URL'),
        ],
        'jazzcash' => [
            'driver' => env('JAZZCASH_GATEWAY_DRIVER'),
            'merchant_id' => env('JAZZCASH_MERCHANT_ID'),
            'password' => env('JAZZCASH_PASSWORD'),
            'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT'),
            'base_url' => env('JAZZCASH_BASE_URL'),
        ],
    ],
];
