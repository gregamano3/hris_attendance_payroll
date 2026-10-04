<?php

namespace App\Features\Attendance\PrintDtr;

use App\Features\Attendance\Queries\AttendanceSummary;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Shared\Period;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Printable Daily Time Record in the style of Civil Service Form No. 48,
 * for HR/payroll (any employee) or employees (their own).
 */
class PrintDtrController
{
    public function __construct(private AttendanceSummary $summary) {}

    public function forEmployee(Request $request, Employee $employee): Response
    {
        return $this->render($employee, Period::fromRequest($request));
    }

    public function mine(Request $request, EmployeeDirectory $directory): Response
    {
        $employee = $directory->forUser($request->user()) ?? abort(403, 'Your account is not linked to an employee record.');

        return $this->render($employee, Period::fromRequest($request));
    }

    private function render(Employee $employee, Period $period): Response
    {
        $employee->loadMissing(['department', 'position']);
        $days = $this->summary->days($employee->id, $period);

        return Pdf::loadView('attendance::dtr-pdf', [
            'employee' => $employee,
            'period' => $period,
            'days' => $days,
            'totals' => $this->summary->totals($days),
        ])->setPaper('a4')->download(sprintf('dtr-%s-%s.pdf', $employee->employee_no, $period->from->format('Ymd')));
    }
}
