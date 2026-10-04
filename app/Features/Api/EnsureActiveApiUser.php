<?php

namespace App\Features\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects tokens of deactivated users and turns the API off when disabled.
 */
class EnsureActiveApiUser
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('hris.api.enabled'), 404);
        abort_unless((bool) $request->user()?->is_active, 403, 'This account is deactivated.');

        return $next($request);
    }
}
