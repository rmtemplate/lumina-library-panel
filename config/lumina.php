<?php

return [
    'qr' => [
        'issuer' => env('QR_ISSUER', 'lumina-library'),
        'audience' => env('QR_AUDIENCE', 'lumina-mobile-app'),
        'signing_secret' => env('QR_SIGNING_SECRET', env('APP_KEY')),
        'logo_path' => env('QR_LOGO_PATH', 'lumina-logo.png'),
        'pickup_ttl_seconds' => (int) env('QR_PICKUP_TTL_SECONDS', 120),
        'return_ttl_seconds' => (int) env('QR_RETURN_TTL_SECONDS', 120),
        'version' => '1.0',
    ],

    'borrowing' => [
        'return_reminder_days' => (int) env('BORROWING_RETURN_REMINDER_DAYS', 2),

        // Default "pickup_expired_after" — how long an awaiting_pickup borrowing
        // stays reservable before it auto-expires. Can be overridden per-install
        // without a deploy via the `reservation_hold_hours` AppSetting (DB-backed).
        // PICKUP_EXPIRE_DAYS (days) is the primary local-dev-friendly knob; the
        // older PICKUP_EXPIRED_AFTER_HOURS (hours) still wins if explicitly set,
        // for back-compat with the previous session's config.
        'pickup_expired_after_hours' => (int) env(
            'PICKUP_EXPIRED_AFTER_HOURS',
            (int) env('PICKUP_EXPIRE_DAYS', 3) * 24
        ),

        // Max loan duration in days — fallback default under the existing
        // `max_borrow_days` AppSetting (see CheckoutService::validateDates()).
        'max_days' => (int) env('BORROWING_MAX_DAYS', 7),

        // Fine accrued per day overdue — fallback default under the existing
        // `fine_per_day` AppSetting (see AppSettingService usages throughout).
        'fine_per_day_default' => (float) env('BORROWING_FINE_PER_DAY', 5000),

        // Flat penalty fee charged when a pickup reservation expires unclaimed
        // (a "no-show"). Paying it lifts the user-level block on new checkouts
        // — see CheckoutService::checkout() and docs/borrowing-lifecycle.md.
        'pickup_noshow_fee' => (float) env('PICKUP_NOSHOW_FEE', 10000),
    ],

    // Allows staff to confirm a pickup without scanning a QR. Intended for
    // local/dev testing with a single device, or as an emergency override.
    // Must stay disabled in production unless explicitly required.
    'staff_bypass' => [
        'enabled' => (bool) env('APP_ENABLE_STAFF_BYPASS', false),
    ],

    // UNAUTHENTICATED, LOCAL-ONLY pickup bypass for single-device testing.
    // Gated at request time by App\Http\Middleware\EnsureDevBypassEnabled,
    // which allows access only when APP_ENV=local, OR APP_DEBUG=true, OR this
    // flag is explicitly enabled. Never rely on this flag alone — the
    // environment/debug checks are the real safety net in production.
    'dev_bypass' => [
        'enabled' => (bool) env('APP_ENABLE_DEV_BYPASS', false),
    ],
];