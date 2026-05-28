<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessAdminPanel()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Akses admin ditolak.'], 403);
            }

            return redirect()->route('unauthorized');
        }

        return $next($request);
    }
}
