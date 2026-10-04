<?php

namespace App\Features\Employees\UpdateEmployee;

use App\Features\Employees\Forms\EmployeeFormData;
use App\Features\Employees\Forms\EmployeeRequest;
use App\Features\Employees\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UpdateEmployeeController
{
    public function edit(Employee $employee, EmployeeFormData $form): View
    {
        return view('employees::employees.edit', $form->for($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('employees.show', $employee)->with('success', "Employee {$employee->full_name} updated.");
    }
}
