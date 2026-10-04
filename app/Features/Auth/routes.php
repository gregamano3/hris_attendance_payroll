<?php

use App\Features\Auth\ForgotPassword\ForgotPasswordController;
use App\Features\Auth\Login\LoginController;
use App\Features\Auth\Logout\LogoutController;
use App\Features\Auth\ResetPassword\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// URLs follow the conventions expected by the AdminLTE auth views.
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    Route::get('password/reset', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('password/email', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('password/reset/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('password/reset', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::post('logout', LogoutController::class)->middleware('auth')->name('logout');
