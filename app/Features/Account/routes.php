<?php

use App\Features\Account\ChangePassword\ChangePasswordController;
use App\Features\Account\ManageTwoFactor\TwoFactorController;
use App\Features\Account\ShowAccount\ShowAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('account', ShowAccountController::class)->name('account');
    Route::put('account/password', ChangePasswordController::class)
        ->middleware('throttle:6,1')
        ->name('account.password');

    Route::prefix('account/two-factor')->name('account.two-factor.')->middleware('throttle:10,1')->group(function () {
        Route::post('/', [TwoFactorController::class, 'enable'])->name('enable');
        Route::post('confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::post('recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes');
        Route::delete('/', [TwoFactorController::class, 'disable'])->name('disable');
    });
});
