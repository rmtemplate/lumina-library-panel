<#
.SYNOPSIS
    Development helper: starts everything needed for realtime local testing
    with a physical Android device (Reverb + the API server bound to all
    interfaces), in the correct order, each in its own window.

.DESCRIPTION
    Equivalent to manually running, in order:
        php artisan optimize:clear
        php artisan reverb:start
        php artisan serve --host=0.0.0.0 --port=8000
    See docs/websocket-local-development.md for why the order matters and
    what each step does.

.EXAMPLE
    cd C:\laragon\www\lumina-library
    .\scripts\dev-start.ps1
#>

$root = Split-Path -Parent $PSScriptRoot

Write-Host "==> Clearing caches (config/route/event/cache)..." -ForegroundColor Cyan
Push-Location $root
php artisan optimize:clear
Pop-Location

Write-Host "==> Current network config:" -ForegroundColor Cyan
Push-Location $root
php artisan lumina:dev-info
Pop-Location

Write-Host "==> Starting Reverb (ws server) in a new window..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$root'; php artisan reverb:start"

Start-Sleep -Seconds 2

Write-Host "==> Starting API server (php artisan serve --host=0.0.0.0 --port=8000) in a new window..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$root'; php artisan serve --host=0.0.0.0 --port=8000"

Write-Host ""
Write-Host "==> Both servers are starting in separate windows. Leave them running." -ForegroundColor Green
Write-Host "==> Now run the Flutter app on your Android device (same WiFi network)." -ForegroundColor Green
Write-Host "==> If the device can't connect, see docs/troubleshooting.md." -ForegroundColor Yellow
