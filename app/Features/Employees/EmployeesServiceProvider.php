<?php

namespace App\Features\Employees;

use App\Features\Employees\ManageCompensation\ApplyCompensationChangesCommand;
use App\Features\Employees\Models\Branch;
use App\Features\Employees\Models\CompensationChange;
use App\Features\Employees\Models\CostCenter;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\Position;
use App\Features\Employees\Queries\EmployeeOptions;
use App\Shared\Providers\FeatureServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class EmployeesServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        // Flush cached select options whenever the reference data changes.
        foreach ([Department::class, Position::class, Branch::class, CostCenter::class] as $model) {
            $model::saved(fn () => EmployeeOptions::flush());
            $model::deleted(fn () => EmployeeOptions::flush());
        }

        $this->commands([ApplyCompensationChangesCommand::class]);
        $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $schedule
            ->command('compensation:apply')->dailyAt('00:05')->withoutOverlapping());

        // Salary history: the hiring rate, and rate edits from the employee form (effective today).
        Employee::created(fn (Employee $employee) => CompensationChange::query()->firstOrCreate(
            ['employee_id' => $employee->id, 'effective_from' => $employee->hired_at->toDateString()],
            ['rate_type' => $employee->rate_type, 'basic_rate' => $employee->basic_rate, 'reason' => 'Hiring rate', 'created_by' => auth()->id()],
        ));
        Employee::updated(function (Employee $employee) {
            if ($employee->wasChanged(['basic_rate', 'rate_type'])) {
                CompensationChange::query()->updateOrCreate(
                    ['employee_id' => $employee->id, 'effective_from' => today()->max($employee->hired_at)->toDateString()],
                    ['rate_type' => $employee->rate_type, 'basic_rate' => $employee->basic_rate, 'reason' => 'Updated on the employee record', 'created_by' => auth()->id()],
                );
            }
        });
    }
}
