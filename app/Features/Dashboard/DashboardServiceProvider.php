<?php

namespace App\Features\Dashboard;

use App\Features\Dashboard\ShowDashboard\GetDashboardStats;
use App\Models\User;
use App\Shared\Providers\FeatureServiceProvider;

class DashboardServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        // Keep the cached widgets in sync with the data they summarise.
        User::saved(fn () => GetDashboardStats::forget());
        User::deleted(fn () => GetDashboardStats::forget());
    }
}
