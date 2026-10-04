<?php

namespace App\Features\Employees\ListEmployees;

use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeOptions;
use App\Shared\Xlsx;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEmployeesController
{
    public function __invoke(Request $request, EmployeeOptions $options): View
    {
        $filters = $this->filters($request);

        return view('employees::employees.index', [
            'employees' => $this->query($filters)->paginate(20)->withQueryString(),
            'filters' => $filters,
            'departments' => $options->departments(),
            'statuses' => EmploymentStatus::options(),
        ]);
    }

    /**
     * The filtered list as Excel (no government IDs or bank details).
     */
    public function export(Request $request): StreamedResponse
    {
        return Xlsx::download('employees-'.today()->format('Ymd').'.xlsx',
            ['Employee no.', 'Last name', 'First name', 'Department', 'Position', 'Branch', 'Type', 'Status', 'Hired', 'Separated'],
            $this->query($this->filters($request))->with('branch')->lazy()->map(fn (Employee $e) => [
                $e->employee_no, $e->last_name, $e->first_name, $e->department?->name, $e->position?->title, $e->branch?->name,
                $e->employment_type->label(), $e->status->label(), $e->hired_at->toDateString(), $e->separated_at?->toDateString(),
            ]),
            'Employees',
        );
    }

    /**
     * @return array{search: string, department: int|null, status: EmploymentStatus|null}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->toString(),
            'department' => $request->integer('department') ?: null,
            'status' => EmploymentStatus::tryFrom($request->string('status')->toString()),
        ];
    }

    /**
     * @param  array{search: string, department: int|null, status: EmploymentStatus|null}  $filters
     * @return Builder<Employee>
     */
    private function query(array $filters): Builder
    {
        return Employee::query()
            ->with(['department', 'position'])
            ->when($filters['search'] !== '', fn ($q) => $q->search($filters['search']))
            ->when($filters['department'], fn ($q, $id) => $q->where('department_id', $id))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->orderBy('last_name')
            ->orderBy('first_name');
    }
}
