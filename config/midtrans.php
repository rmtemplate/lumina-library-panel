<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'payment_expiry_minutes' => (int) env('MIDTRANS_PAYMENT_EXPIRY_MINUTES', 60),
    'notification_url' => env('MIDTRANS_NOTIFICATION_URL', ''),
    'finish_redirect_url' => env('MIDTRANS_FINISH_REDIRECT_URL', ''),
    'enabled_payments' => ['qris', 'bank_transfer'],
    'snap' => [
        'sandbox_url' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
        'production_url' => 'https://app.midtrans.com/snap/v1/transactions',
    ],
    'api' => [
        'sandbox_url' => 'https://api.sandbox.midtrans.com/v2',
        'production_url' => 'https://api.midtrans.com/v2',
    ],
];
