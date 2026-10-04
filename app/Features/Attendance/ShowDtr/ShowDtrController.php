<?php

namespace App\Features\Attendance\ShowDtr;

use App\Features\Attendance\Queries\AttendanceSummary;
use App\Features\Employees\Models\Employee;
use App\Shared\Period;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowDtrController
{
    public function __invoke(Request $request, AttendanceSummary $summary): View
    {
        $period = Period::fromRequest($request);
        $employee = $request->filled('employee')
            ? Employee::withTrashed()->with(['department', 'position'])->find($request->integer('employee'))
            : null;
        $days = $employee ? $summary->days($employee->id, $period) : collect();

        return view('attendance::dtr', [
            'employees' => Employee::query()->orderBy('last_name')->orderBy('first_name')->get(['id', 'employee_no', 'first_name', 'last_name', 'middle_name', 'suffix']),
            'employee' => $employee,
            'period' => $period,
            'days' => $days,
            'totals' => $summary->totals($days),
        ]);
    }
}
