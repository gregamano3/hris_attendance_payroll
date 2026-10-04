<?php

namespace App\Features\Dashboard;

use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Dashboard\ShowDashboard\GetDashboardStats;
use App\Features\Employees\Models\Employee;
use App\Models\User;
use App\Shared\Providers\FeatureServiceProvider;

class DashboardServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        // Keep the cached widgets in sync with the data they summarise.
        foreach ([User::class, Employee::class, LeaveRequest::class] as $model) {
            $model::saved(fn () => GetDashboardStats::forget());
            $model::deleted(fn () => GetDashboardStats::forget());
        }
    }
}
