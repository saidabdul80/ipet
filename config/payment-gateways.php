<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used
    | when no specific gateway is requested.
    |
    */
    'default' => env('PAYMENT_GATEWAY_DEFAULT', 'paystack'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment gateways for your application.
    | Each gateway can have its own configuration and credentials.
    |
    */
    'gateways' => [
        'paystack' => [
            'name' => 'Paystack',
            'driver' => 'paystack',
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'webhook_url' => env('APP_URL') . '/api/webhooks/paystack',
            'callback_url' => env('FRONTEND_URL') . '/wallet/payment-callback',
            'currency' => 'NGN',
            'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
        ],

        'monnify' => [
            'name' => 'Monnify',
            'driver' => 'monnify',
            'api_key' => env('MONNIFY_API_KEY'),
            'secret_key' => env('MONNIFY_SECRET_KEY'),
            'contract_code' => env('MONNIFY_CONTRACT_CODE'),
            'base_url' => env('MONNIFY_BASE_URL', 'https://sandbox.monnify.com'),
            'webhook_url' => env('APP_URL') . '/api/webhooks/monnify',
            'callback_url' => env('FRONTEND_URL') . '/wallet/payment-callback',
            'currency' => 'NGN',
        ],

        'palmpay' => [
            'name' => 'PalmPay',
            'driver' => 'palmpay',
            'merchant_id' => env('PALMPAY_MERCHANT_ID'),
            'api_key' => env('PALMPAY_API_KEY'),
            'secret_key' => env('PALMPAY_SECRET_KEY'),
            'base_url' => env('PALMPAY_BASE_URL', 'https://api.palmpay.com'),
            'webhook_url' => env('APP_URL') . '/api/webhooks/palmpay',
            'callback_url' => env('FRONTEND_URL') . '/wallet/payment-callback',
            'currency' => 'NGN',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    |
    | Enable webhook signature verification for security
    |
    */
    'verify_webhooks' => env('PAYMENT_VERIFY_WEBHOOKS', true),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | General payment configuration
    |
    */
    'settings' => [
        'min_amount' => 100, // Minimum payment amount in kobo/cents
        'max_amount' => 10000000, // Maximum payment amount in kobo/cents
        'auto_approve_threshold' => 50000, // Auto-approve payments below this amount
        'require_approval' => true, // Require admin approval for large payments
    ],
];

