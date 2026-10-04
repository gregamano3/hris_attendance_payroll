<?php

namespace App\Features\Employees\ShowEmployee;

use App\Features\Employees\Models\Employee;
use Illuminate\View\View;

class ShowEmployeeController
{
    public function __invoke(Employee $employee): View
    {
        $employee->load(['department', 'position', 'user', 'documents']);

        return view('employees::employees.show', compact('employee'));
    }
}
