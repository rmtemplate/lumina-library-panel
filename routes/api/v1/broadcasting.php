<?php

use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Broadcasting (WebSocket channel) Auth
|--------------------------------------------------------------------------
|
| Laravel's default `/broadcasting/auth` route (registered automatically by
| `withRouting(channels: ...)` in bootstrap/app.php) uses the 'web' guard,
| which expects a session — not usable by a token-based mobile client.
|
| This route does the exact same thing (delegates to the framework's own
| Illuminate\Broadcasting\BroadcastController@authenticate, which just calls
| Broadcast::auth($request)) but behind `auth:sanctum`, so a Flutter client
| sending `Authorization: Bearer {token}` can authorize private channel
| subscriptions (see routes/channels.php for the channel definitions).
|
| POST /api/v1/broadcasting/auth
| Body: { "socket_id": "...", "channel_name": "private-user.{id}" }
|
*/

Route::middleware('auth:sanctum')
    ->post('broadcasting/auth', [BroadcastController::class, 'authenticate'])
    ->name('broadcasting.auth');
