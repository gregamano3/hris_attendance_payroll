<?php

namespace App\Features\Auth\Login;

use App\Features\Auth\TwoFactorChallenge\TwoFactorChallengeController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController
{
    public function create(): View
    {
        return view('auth::login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Users with two-factor authentication complete the sign-in on the challenge page.
        $user = $request->user();

        if ($user?->hasTwoFactorEnabled()) {
            Auth::guard('web')->logout();
            $request->session()->regenerate();
            $request->session()->put(TwoFactorChallengeController::SESSION_KEY, [
                'id' => $user->id, 'remember' => $request->boolean('remember'), 'at' => now()->timestamp,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        $request->user()?->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('dashboard'));
    }
}
