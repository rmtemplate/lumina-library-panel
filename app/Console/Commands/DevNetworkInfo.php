<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Development helper: prints the exact URLs/ports the app is currently
 * configured to use, plus quick sanity checks, so debugging "Flutter can't
 * connect" doesn't start with re-deriving config by hand every time.
 *
 * Usage: php artisan lumina:dev-info
 */
class DevNetworkInfo extends Command
{
    protected $signature = 'lumina:dev-info';

    protected $description = 'Print current network config (API/Reverb URLs, ports, env) for local device testing.';

    public function handle(): int
    {
        $this->components->info('Lumina Library — Local Network Info');

        $this->table(['Setting', 'Value'], [
            ['APP_ENV', app()->environment()],
            ['APP_DEBUG', config('app.debug') ? 'true' : 'false'],
            ['APP_URL (API base)', config('app.url')],
            ['Reverb bind host:port (REVERB_SERVER_HOST/PORT)', config('reverb.servers.reverb.host') . ':' . config('reverb.servers.reverb.port')],
            ['Reverb advertised host:port (REVERB_HOST/PORT)', config('reverb.apps.apps.0.options.host') . ':' . config('reverb.apps.apps.0.options.port')],
            ['Reverb scheme', config('reverb.apps.apps.0.options.scheme')],
            ['Reverb app key', config('reverb.apps.apps.0.key')],
            ['Staff bypass enabled', config('lumina.staff_bypass.enabled') ? 'true' : 'false'],
            ['Dev bypass enabled (flag only — see below)', config('lumina.dev_bypass.enabled') ? 'true' : 'false'],
        ]);

        $this->newLine();
        $this->components->info('Flutter NetworkConfig should match:');
        $this->line('  apiBaseUrl   = ' . config('app.url'));
        $this->line('  wsHost       = ' . config('reverb.apps.apps.0.options.host'));
        $this->line('  wsPort       = ' . config('reverb.apps.apps.0.options.port'));
        $this->line('  reverbAppKey = ' . config('reverb.apps.apps.0.key'));

        $this->newLine();
        $devBypassActuallyOpen = app()->environment('local')
            || (bool) config('app.debug')
            || (bool) config('lumina.dev_bypass.enabled');
        $this->components->info(
            'Dev bypass (/api/dev/*) is currently: ' . ($devBypassActuallyOpen ? 'OPEN (env/debug/flag allows it)' : 'BLOCKED')
        );

        $this->newLine();
        $this->warn('Reminder: REVERB_SERVER_HOST must be 0.0.0.0 (not 127.0.0.1) for a phone on the same WiFi to connect.');
        if (config('reverb.servers.reverb.host') !== '0.0.0.0') {
            $this->error('REVERB_SERVER_HOST is NOT 0.0.0.0 — a physical device will NOT be able to connect to Reverb.');
        }

        if (config('app.url') && str_contains((string) config('app.url'), 'localhost')) {
            $this->error('APP_URL still contains "localhost" — a physical device will NOT be able to reach the API.');
        }

        return self::SUCCESS;
    }
}
