<?php

namespace App\Features\Auth\ForgotPassword;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController
{
    public function create(): View
    {
        return view('adminlte::auth.passwords.email');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Always report success so the form can't be used to discover accounts.
        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
