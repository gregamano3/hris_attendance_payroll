<?php

namespace App\Features\Payroll\ShowPayrollRun;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\ExportBankFile\ExportBankFileController;
use App\Features\Payroll\Models\PayrollRun;
use App\Shared\Money\Money;
use Illuminate\View\View;

class ShowPayrollRunController
{
    public function __invoke(PayrollRun $run): View
    {
        $run->load(['finalizer']);

        $payslips = $run->payslips()->orderBy('employee_name')->get();

        return view('payroll::runs.show', [
            'run' => $run,
            'payslips' => $payslips,
            'costSummary' => $payslips->groupBy(fn ($p) => ($p->branch ?? 'No branch').' · '.($p->cost_center ?? 'No cost center'))
                ->map(fn ($group) => [
                    'count' => $group->count(),
                    'gross' => Money::ofCentavos((int) $group->sum(fn ($p) => $p->gross_pay->centavos)),
                    'net' => Money::ofCentavos((int) $group->sum(fn ($p) => $p->net_pay->centavos)),
                    'employer' => Money::ofCentavos((int) $group->sum(fn ($p) => $p->employer_contributions->centavos)),
                ])->sortKeys(),
            'adjustments' => $run->adjustments()->with('employee')->latest()->get(),
            'withoutBank' => $run->isLocked() ? ExportBankFileController::withoutBank($run) : collect(),
            'employees' => Employee::query()->orderBy('last_name')->orderBy('first_name')->get()
                ->mapWithKeys(fn (Employee $e): array => [$e->id => "{$e->employee_no} — {$e->full_name}"])
                ->all(),
        ]);
    }
}
