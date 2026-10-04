<?php

namespace App\Features\Auth\Login;

use Illuminate\Http\RedirectResponse;
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

        $request->session()->regenerate();

        $request->user()?->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('dashboard'));
    }
}
