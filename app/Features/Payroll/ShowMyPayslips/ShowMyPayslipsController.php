<?php

namespace App\Features\Payroll\ShowMyPayslips;

use App\Features\Employees\Queries\EmployeeDirectory;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowMyPayslipsController
{
    public function __invoke(Request $request, EmployeeDirectory $directory): View
    {
        $employee = $directory->forUser($request->user());

        $payslips = $employee
            ? Payslip::query()
                ->with('run')
                ->where('employee_id', $employee->id)
                ->whereHas('run', fn ($q) => $q->where('status', PayrollRunStatus::Finalized))
                ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
                ->orderByDesc('payroll_runs.period_end')
                ->select('payslips.*')
                ->paginate(12)
            : null;

        return view('payroll::payslips.mine', compact('employee', 'payslips'));
    }
}
