<?php

namespace App\Features\Employees;

use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Position;
use App\Features\Employees\Queries\EmployeeOptions;
use App\Shared\Providers\FeatureServiceProvider;

class EmployeesServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        // Flush cached select options whenever the reference data changes.
        foreach ([Department::class, Position::class] as $model) {
            $model::saved(fn () => EmployeeOptions::flush());
            $model::deleted(fn () => EmployeeOptions::flush());
        }
    }
}
