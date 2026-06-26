<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureDevBypassEnabled;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Support\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'role' => RoleMiddleware::class,
            'super_admin' => SuperAdminMiddleware::class,
            'dev_bypass' => EnsureDevBypassEnabled::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::validation(
                    errors: $e->errors(),
                    message: 'Validasi gagal.',
                    code: 'VALIDATION_ERROR'
                );
            }

            return null;
        });
    })->create();
