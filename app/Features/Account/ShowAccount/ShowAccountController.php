<?php

namespace App\Features\Account\ShowAccount;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowAccountController
{
    public function __invoke(Request $request): View
    {
        return view('account::show', ['user' => $request->user()]);
    }
}
