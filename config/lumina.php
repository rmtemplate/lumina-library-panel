<?php

return [
    'qr' => [
        'issuer' => env('QR_ISSUER', 'lumina-library'),
        'audience' => env('QR_AUDIENCE', 'lumina-mobile-app'),
        'signing_secret' => env('QR_SIGNING_SECRET', env('APP_KEY')),
        'logo_path' => env('QR_LOGO_PATH', 'lumina-logo.png'),
        'version' => '1.0',
    ],
];