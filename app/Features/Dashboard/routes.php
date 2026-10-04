<?php

use App\Features\Dashboard\ShowDashboard\ShowDashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('dashboard', ShowDashboardController::class)
    ->middleware(['auth', 'can:dashboard.view'])
    ->name('dashboard');
