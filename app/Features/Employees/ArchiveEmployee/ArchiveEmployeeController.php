<?php

namespace App\Features\Employees\ArchiveEmployee;

use App\Features\Employees\Models\Employee;
use Illuminate\Http\RedirectResponse;

/**
 * Soft deletes the record so historical attendance and payroll stay intact.
 */
class ArchiveEmployeeController
{
    public function __invoke(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', "Employee {$employee->full_name} archived.");
    }
}
