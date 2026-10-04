<?php

namespace App\Features\Employees\CreateEmployee;

use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Enums\EmploymentType;
use App\Features\Employees\Enums\RateType;
use App\Features\Employees\Forms\EmployeeFormData;
use App\Features\Employees\Forms\EmployeeRequest;
use App\Features\Employees\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CreateEmployeeController
{
    public function create(EmployeeFormData $form): View
    {
        $employee = new Employee([
            'employee_no' => $this->nextEmployeeNo(),
            'employment_type' => EmploymentType::Probationary,
            'status' => EmploymentStatus::Active,
            'rate_type' => RateType::Monthly,
            'hired_at' => today(),
        ]);

        return view('employees::employees.create', $form->for($employee));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::query()->create($request->validated());

        return redirect()->route('employees.show', $employee)->with('success', "Employee {$employee->full_name} created.");
    }

    private function nextEmployeeNo(): string
    {
        $next = (Employee::withTrashed()->max('id') ?? 0) + 1;

        return sprintf('EMP-%05d', $next);
    }
}
