<?php

namespace App\Features\Account\ChangePassword;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ChangePasswordController
{
    public function __invoke(ChangePasswordRequest $request): RedirectResponse
    {
        $password = $request->validated('password');

        $request->user()?->forceFill(['password' => $password])->save();

        // Re-hashes the session password so AuthenticateSession logs out every other device.
        Auth::logoutOtherDevices($password);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'Your password was changed. Other sessions were signed out.');
    }
}
