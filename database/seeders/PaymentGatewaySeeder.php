<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentGateway;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Paystack Gateway
        PaymentGateway::firstOrCreate(
            ['driver' => 'paystack'],
            [
                'name' => 'Paystack',
                'display_name' => 'Paystack Payment Gateway',
                'description' => 'Accept payments via Paystack - Cards, Bank Transfer, USSD, and more',
                'credentials' => [
                    'public_key' => env('PAYSTACK_PUBLIC_KEY', 'pk_test_fcb9be86e102a78824897afdc2d78cd77b07cc68'),
                    'secret_key' => env('PAYSTACK_SECRET_KEY', 'sk_test_65210fab75f042aa57f7ef9588c8daa7842da281'),
                ],
                'settings' => [
                    'test_mode' => true,
                ],
                'is_active' => true,
                'is_default' => true,
                'currency' => 'NGN',
                'supported_channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
                'webhook_url' => env('APP_URL') . '/api/webhooks/paystack',
                'callback_url' => env('FRONTEND_URL', 'http://localhost:5173') . '/wallet/payment-callback',
                'priority' => 100,
            ]
        );

        // Monnify Gateway (inactive by default)
        PaymentGateway::firstOrCreate(
            ['driver' => 'monnify'],
            [
                'name' => 'Monnify',
                'display_name' => 'Monnify Payment Gateway',
                'description' => 'Accept payments via Monnify - Bank Transfer and Cards',
                'credentials' => [
                    'api_key' => env('MONNIFY_API_KEY', ''),
                    'secret_key' => env('MONNIFY_SECRET_KEY', ''),
                    'contract_code' => env('MONNIFY_CONTRACT_CODE', ''),
                ],
                'settings' => [
                    'base_url' => env('MONNIFY_BASE_URL', 'https://sandbox.monnify.com'),
                    'test_mode' => true,
                ],
                'is_active' => false,
                'is_default' => false,
                'currency' => 'NGN',
                'supported_channels' => ['bank_transfer', 'card'],
                'webhook_url' => env('APP_URL') . '/api/webhooks/monnify',
                'callback_url' => env('FRONTEND_URL', 'http://localhost:5173') . '/wallet/payment-callback',
                'priority' => 50,
            ]
        );

        // PalmPay Gateway (inactive by default)
        PaymentGateway::firstOrCreate(
            ['driver' => 'palmpay'],
            [
                'name' => 'PalmPay',
                'display_name' => 'PalmPay Payment Gateway',
                'description' => 'Accept payments via PalmPay',
                'credentials' => [
                    'merchant_id' => env('PALMPAY_MERCHANT_ID', ''),
                    'api_key' => env('PALMPAY_API_KEY', ''),
                    'secret_key' => env('PALMPAY_SECRET_KEY', ''),
                ],
                'settings' => [
                    'base_url' => env('PALMPAY_BASE_URL', 'https://api.palmpay.com'),
                    'test_mode' => true,
                ],
                'is_active' => false,
                'is_default' => false,
                'currency' => 'NGN',
                'supported_channels' => ['wallet', 'card'],
                'webhook_url' => env('APP_URL') . '/api/webhooks/palmpay',
                'callback_url' => env('FRONTEND_URL', 'http://localhost:5173') . '/wallet/payment-callback',
                'priority' => 25,
            ]
        );
    }
}

