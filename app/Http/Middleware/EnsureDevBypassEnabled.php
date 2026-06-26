<?php

namespace App\Http\Middleware;

use App\Support\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the unauthenticated, local-only pickup bypass routes.
 *
 * This is the ONLY thing standing between the dev bypass endpoint and the
 * public internet — it deliberately does not check authentication at all.
 * Access is allowed only when:
 *   - APP_ENV=local, OR
 *   - APP_DEBUG=true, OR
 *   - lumina.dev_bypass.enabled (APP_ENABLE_DEV_BYPASS=true) is explicitly set
 *
 * Any of the three is sufficient on its own, matching the requested
 * "fail open for local dev, fail closed everywhere else" behavior. In a real
 * production deployment APP_ENV will be "production" and APP_DEBUG will be
 * false, so this middleware blocks the route unless someone deliberately
 * flips APP_ENABLE_DEV_BYPASS — which should never happen outside local dev.
 */
class EnsureDevBypassEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEnabled()) {
            return ApiResponse::forbidden(
                message: 'Development bypass is disabled.',
                code: 'DEV_BYPASS_DISABLED'
            );
        }

        return $next($request);
    }

    private function isEnabled(): bool
    {
        return app()->environment('local')
            || (bool) config('app.debug')
            || (bool) config('lumina.dev_bypass.enabled');
    }
}
