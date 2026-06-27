<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    // Core API charge options (see MidtransService::charge*()).
    // "Sanitized" strips potentially unsafe characters from request fields
    // before sending to Midtrans; "3DS" forces 3-D Secure on card payments
    // (not used by the payment methods this app supports today, but kept
    // configurable since Midtrans' SDKs expose it as a standard option).
    'sanitized' => (bool) env('MIDTRANS_SANITIZED', true),
    'three_ds' => (bool) env('MIDTRANS_3DS', true),

    'payment_expiry_minutes' => (int) env('MIDTRANS_PAYMENT_EXPIRY_MINUTES', 60),
    'notification_url' => env('MIDTRANS_NOTIFICATION_URL', ''),
    'finish_redirect_url' => env('MIDTRANS_FINISH_REDIRECT_URL', ''),

    // Legacy Snap config — kept for backward compatibility only.
    // New payment creation uses the Core API `charge` methods exclusively
    // (see MidtransService::chargeQris/chargeBankTransfer/etc and
    // docs/midtrans.md) — Snap is intentionally not used for new flows.
    'enabled_payments' => ['qris', 'bank_transfer'],
    'snap' => [
        'sandbox_url' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
        'production_url' => 'https://app.midtrans.com/snap/v1/transactions',
    ],

    'api' => [
        'sandbox_url' => 'https://api.sandbox.midtrans.com/v2',
        'production_url' => 'https://api.midtrans.com/v2',
    ],

    // Maps each member-facing `payment_method` value to the Midtrans VA
    // "bank" code used in the Core API `bank_transfer` charge payload.
    'va_banks' => [
        'bca_va' => 'bca',
        'bni_va' => 'bni',
        'bri_va' => 'bri',
        'permata_va' => 'permata',
    ],

    // Every payment_method this app exposes to members — used to validate
    // POST /payments/create's `payment_method` field.
    'supported_methods' => [
        'qris', 'bca_va', 'bni_va', 'bri_va', 'mandiri_bill', 'permata_va', 'gopay', 'shopeepay',
    ],
];
