<?php

use App\Features\Health\CheckHealth\CheckHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', CheckHealthController::class)->name('health');
