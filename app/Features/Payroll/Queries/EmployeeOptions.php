<?php

namespace App\Features\Payroll\Queries;

use App\Features\Employees\Models\Employee;

class EmployeeOptions
{
    /**
     * @return array<int, string>
     */
    public function active(): array
    {
        return Employee::query()->orderBy('last_name')->orderBy('first_name')->get()
            ->mapWithKeys(fn (Employee $e): array => [$e->id => "{$e->employee_no} — {$e->full_name}"])
            ->all();
    }
}
