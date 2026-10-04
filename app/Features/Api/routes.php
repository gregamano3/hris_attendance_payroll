<?php

use App\Features\Api\ManageTokens\ApiTokensController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('account/api-tokens')->name('account.api-tokens')->group(function () {
    Route::get('/', [ApiTokensController::class, 'index']);
    Route::post('/', [ApiTokensController::class, 'store'])->middleware('throttle:10,1')->name('.store');
    Route::delete('{token}', [ApiTokensController::class, 'destroy'])->whereNumber('token')->name('.destroy');
});
