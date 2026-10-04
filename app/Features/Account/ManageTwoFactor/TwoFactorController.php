<?php

namespace App\Features\Account\ManageTwoFactor;

use App\Shared\TwoFactor\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Enable (secret + QR), confirm with a code, regenerate recovery codes and
 * disable two-factor authentication for the signed-in user.
 */
class TwoFactorController
{
    public function __construct(private TwoFactor $twoFactor) {}

    public function enable(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        $request->user()?->forceFill([
            'two_factor_secret' => $this->twoFactor->generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('account')->with('status', 'Scan the QR code with your authenticator app, then enter the 6-digit code to finish.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if ($user?->two_factor_secret === null || ! $this->twoFactor->verify($user->two_factor_secret, $request->string('code')->toString())) {
            throw ValidationException::withMessages(['code' => 'The code is invalid. Check the time on your phone and try again.']);
        }

        $codes = $this->twoFactor->recoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes->all()])->save();

        return redirect()->route('account')
            ->with('success', 'Two-factor authentication is on.')
            ->with('recovery_codes', $codes->all());
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $user = $request->user();
        abort_unless($user?->hasTwoFactorEnabled(), 409);

        $codes = $this->twoFactor->recoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes->all()])->save();

        return redirect()->route('account')->with('success', 'New recovery codes generated. The old ones no longer work.')->with('recovery_codes', $codes->all());
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $user = $request->user();

        if ($user !== null && $this->twoFactor->isRequiredFor($user)) {
            return back()->with('error', 'Two-factor authentication is required for your role and cannot be turned off.');
        }

        $user?->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();

        return redirect()->route('account')->with('success', 'Two-factor authentication is off.');
    }
}
