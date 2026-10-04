<?php

namespace App\Features\Employees\Queries;

use App\Features\Employees\Models\Employee;
use App\Models\User;

/**
 * Read access to employee records for other features (dashboard, attendance,
 * payroll) without coupling them to the Employees slices.
 */
class EmployeeDirectory
{
    public function activeCount(): int
    {
        return Employee::query()->active()->count();
    }

    public function forUser(User $user): ?Employee
    {
        return Employee::query()->where('user_id', $user->id)->first();
    }
}
