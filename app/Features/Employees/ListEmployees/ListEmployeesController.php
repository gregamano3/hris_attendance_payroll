<?php

namespace App\Features\Employees\ListEmployees;

use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeOptions;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListEmployeesController
{
    public function __invoke(Request $request, EmployeeOptions $options): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'department' => $request->integer('department') ?: null,
            'status' => EmploymentStatus::tryFrom($request->string('status')->toString()),
        ];

        $employees = Employee::query()
            ->with(['department', 'position'])
            ->when($filters['search'] !== '', fn ($q) => $q->search($filters['search']))
            ->when($filters['department'], fn ($q, $id) => $q->where('department_id', $id))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('employees::employees.index', [
            'employees' => $employees,
            'filters' => $filters,
            'departments' => $options->departments(),
            'statuses' => EmploymentStatus::options(),
        ]);
    }
}
