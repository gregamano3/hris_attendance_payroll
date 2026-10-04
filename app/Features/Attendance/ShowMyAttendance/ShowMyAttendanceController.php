<?php

namespace App\Features\Attendance\ShowMyAttendance;

use App\Features\Attendance\Queries\AttendanceSummary;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Shared\Period;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowMyAttendanceController
{
    public function __invoke(Request $request, EmployeeDirectory $directory, AttendanceSummary $summary): View
    {
        $employee = $directory->forUser($request->user());
        $period = Period::fromRequest($request);
        $days = $employee ? $summary->days($employee->id, $period) : collect();

        return view('attendance::my-attendance', [
            'employee' => $employee,
            'period' => $period,
            'days' => $days,
            'totals' => $summary->totals($days),
        ]);
    }
}
