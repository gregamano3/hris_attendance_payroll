<?php

use App\Features\Analytics\ShowAnalytics\ShowAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:employees.view'])->group(function () {
    Route::get('analytics', [ShowAnalyticsController::class, 'show'])->name('analytics');
    Route::get('analytics/export.xlsx', [ShowAnalyticsController::class, 'export'])->name('analytics.export');
});
