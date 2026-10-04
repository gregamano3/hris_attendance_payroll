<?php

namespace App\Shared\TwoFactor;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends users whose role requires two-factor authentication to the account
 * page until they have enabled it.
 */
class RequireTwoFactor
{
    public function __construct(private TwoFactor $twoFactor) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null
            && ! $user->hasTwoFactorEnabled()
            && ! $request->routeIs('account', 'account.*', 'logout')
            && $this->twoFactor->isRequiredFor($user)) {
            return redirect()->route('account')->with('warning', 'Your role requires two-factor authentication. Please enable it to continue.');
        }

        return $next($request);
    }
}
