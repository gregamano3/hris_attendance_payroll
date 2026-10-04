<?php

namespace App\Features\Employees\ManageDepartments;

use App\Features\Employees\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentsController
{
    public function index(): View
    {
        $departments = Department::query()
            ->withCount(['employees', 'positions'])
            ->orderBy('name')
            ->paginate(20);

        return view('employees::departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('employees::departments.form', ['department' => new Department]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $department = Department::query()->create($request->validated());

        return redirect()->route('departments.index')->with('success', "Department {$department->name} created.");
    }

    public function edit(Department $department): View
    {
        return view('employees::departments.form', compact('department'));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()->route('departments.index')->with('success', "Department {$department->name} updated.");
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->withTrashed()->exists()) {
            return back()->with('error', 'Departments with employees cannot be deleted.');
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', "Department {$department->name} deleted.");
    }
}
