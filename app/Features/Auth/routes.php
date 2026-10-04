<?php

use App\Features\Auth\ForgotPassword\ForgotPasswordController;
use App\Features\Auth\Login\LoginController;
use App\Features\Auth\Logout\LogoutController;
use App\Features\Auth\ResetPassword\ResetPasswordController;
use App\Features\Auth\SingleSignOn\SingleSignOnController;
use App\Features\Auth\TwoFactorChallenge\TwoFactorChallengeController;
use Illuminate\Support\Facades\Route;

// URLs follow the conventions expected by the AdminLTE auth views.
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:'.config('hris.login_throttle_per_minute').',1');

    Route::get('password/reset', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('password/email', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('password/reset/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('password/reset', [ResetPasswordController::class, 'store'])->name('password.update');

    Route::get('auth/sso/redirect', [SingleSignOnController::class, 'redirect'])->middleware('throttle:20,1')->name('sso.redirect');
    Route::get('auth/sso/callback', [SingleSignOnController::class, 'callback'])->middleware('throttle:20,1')->name('sso.callback');

    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('two-factor.challenge.store');
});

Route::post('logout', LogoutController::class)->middleware('auth')->name('logout');
