<?php

namespace App\Features\Employees\Api;

use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class EmployeesApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $employees = Employee::query()
            ->with(['department', 'position', 'branch'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->when($filters['branch_id'] ?? null, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['updated_since'] ?? null, fn ($q, $since) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();

        return EmployeeResource::collection($employees);
    }

    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee->load(['department', 'position', 'branch']));
    }
}
