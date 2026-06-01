<?php

return [
    'qr' => [
        'issuer' => env('QR_ISSUER', 'lumina-library'),
        'audience' => env('QR_AUDIENCE', 'lumina-mobile-app'),
        'signing_secret' => env('QR_SIGNING_SECRET', env('APP_KEY')),
        'logo_path' => env('QR_LOGO_PATH', 'lumina-logo.png'),
        'pickup_ttl_seconds' => (int) env('QR_PICKUP_TTL_SECONDS', 60),
        'return_ttl_seconds' => (int) env('QR_RETURN_TTL_SECONDS', 60),
        'version' => '1.0',
    ],

    'borrowing' => [
        'return_reminder_days' => (int) env('BORROWING_RETURN_REMINDER_DAYS', 2),
    ],
];