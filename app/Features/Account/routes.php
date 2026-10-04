<?php

use App\Features\Account\ChangePassword\ChangePasswordController;
use App\Features\Account\ShowAccount\ShowAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('account', ShowAccountController::class)->name('account');
    Route::put('account/password', ChangePasswordController::class)
        ->middleware('throttle:6,1')
        ->name('account.password');
});
