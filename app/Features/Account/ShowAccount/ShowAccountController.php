<?php

namespace App\Features\Account\ShowAccount;

use App\Shared\TwoFactor\TwoFactor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowAccountController
{
    public function __invoke(Request $request, TwoFactor $twoFactor): View
    {
        $user = $request->user();
        $pending = $user?->two_factor_secret !== null && ! $user->hasTwoFactorEnabled();

        return view('account::show', [
            'user' => $user,
            'twoFactorPending' => $pending,
            'twoFactorQr' => $pending ? $twoFactor->qrCodeSvg($user, (string) $user->two_factor_secret) : null,
            'twoFactorRequired' => $user !== null && $twoFactor->isRequiredFor($user),
        ]);
    }
}
