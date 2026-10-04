<?php

use App\Features\Api\Docs\OpenApiController;
use App\Features\Api\Me\MeController;
use Illuminate\Support\Facades\Route;

Route::get('v1/openapi.yaml', OpenApiController::class)->name('v1.openapi');

Route::prefix('v1')->name('v1.')->middleware('api.v1')->group(function () {
    Route::get('me', MeController::class)->middleware('abilities:profile:read')->name('me');
});
