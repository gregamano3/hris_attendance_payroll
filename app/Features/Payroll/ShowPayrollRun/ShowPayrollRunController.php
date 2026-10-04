<?php

namespace App\Features\Payroll\ShowPayrollRun;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\ExportBankFile\ExportBankFileController;
use App\Features\Payroll\Models\PayrollRun;
use Illuminate\View\View;

class ShowPayrollRunController
{
    public function __invoke(PayrollRun $run): View
    {
        $run->load(['finalizer']);

        return view('payroll::runs.show', [
            'run' => $run,
            'payslips' => $run->payslips()->orderBy('employee_name')->get(),
            'adjustments' => $run->adjustments()->with('employee')->latest()->get(),
            'withoutBank' => $run->isLocked() ? ExportBankFileController::withoutBank($run) : collect(),
            'employees' => Employee::query()->orderBy('last_name')->orderBy('first_name')->get()
                ->mapWithKeys(fn (Employee $e): array => [$e->id => "{$e->employee_no} — {$e->full_name}"])
                ->all(),
        ]);
    }
}
