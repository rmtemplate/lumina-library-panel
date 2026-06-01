@echo off
cd /d C:\laragon\www\lumina-library

echo Starting Lumina Library background services...

start "Lumina Library - Reverb" cmd /k "php artisan reverb:start"
start "Lumina Library - Scheduler" cmd /k "php artisan schedule:work"
start "Lumina Library - Queue" cmd /k "php artisan queue:work"

echo.
echo Lumina Library background services started.
echo Reverb, Scheduler, and Queue are running in separate windows.
pause