<?php

namespace App\Features\Auth\TwoFactorChallenge;

use App\Models\User;
use App\Shared\TwoFactor\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Second login step for users with two-factor authentication: a TOTP code
 * from the authenticator app or a single-use recovery code.
 */
class TwoFactorChallengeController
{
    public const SESSION_KEY = 'login.two_factor';

    private const EXPIRES_AFTER_SECONDS = 600;

    public function create(Request $request): View|RedirectResponse
    {
        return $this->pendingUser($request) ? view('auth::two-factor-challenge') : redirect()->route('login');
    }

    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in expired. Please sign in again.']);
        }

        $request->validate(['code' => ['nullable', 'string'], 'recovery_code' => ['nullable', 'string']]);

        $valid = match (true) {
            $request->filled('code') => $twoFactor->verify((string) $user->two_factor_secret, $request->string('code')->toString()),
            $request->filled('recovery_code') => $this->useRecoveryCode($user, $request->string('recovery_code')->trim()->lower()->toString()),
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages([$request->filled('recovery_code') ? 'recovery_code' : 'code' => 'The code is invalid.']);
        }

        $remember = (bool) $request->session()->pull(self::SESSION_KEY.'.remember', false);
        $request->session()->forget(self::SESSION_KEY);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('dashboard'));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! is_array($pending) || now()->timestamp - (int) ($pending['at'] ?? 0) > self::EXPIRES_AFTER_SECONDS) {
            return null;
        }

        $user = User::query()->where('is_active', true)->find($pending['id'] ?? null);

        return $user?->hasTwoFactorEnabled() ? $user : null;
    }

    private function useRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->forceFill(['two_factor_recovery_codes' => array_values(array_diff($codes, [$code]))])->save();

        return true;
    }
}
