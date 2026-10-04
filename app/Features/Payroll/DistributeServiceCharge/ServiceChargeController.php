<?php

namespace App\Features\Payroll\DistributeServiceCharge;

use App\Features\Payroll\Compute\PayslipLine;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollAdjustment;
use App\Features\Payroll\Models\PayrollRun;
use App\Shared\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * RA 11360: service charges collected are distributed 100% and equally to
 * the covered employees. Creates one taxable adjustment per employee of the
 * computed run (remaining centavos go to the first employees).
 */
class ServiceChargeController
{
    public function __invoke(Request $request, PayrollRun $run): RedirectResponse
    {
        abort_if($run->isLocked() || $run->isComputing() || $run->isThirteenthMonth(), 409);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer'],
        ]);

        $employeeIds = $run->payslips()->when($data['employee_ids'] ?? null, fn ($q, $ids) => $q->whereIn('employee_id', $ids))->pluck('employee_id');

        if ($employeeIds->isEmpty()) {
            return back()->with('error', 'Compute the run first: the service charge is split among its employees.');
        }

        $total = Money::ofPesos($data['amount']);
        $share = intdiv($total->centavos, $employeeIds->count());
        $remainder = $total->centavos - $share * $employeeIds->count();

        DB::transaction(function () use ($run, $employeeIds, $share, $remainder, $request) {
            PayrollAdjustment::query()->where(['payroll_run_id' => $run->id, 'code' => 'SERVICE_CHARGE'])->delete();

            foreach ($employeeIds->values() as $i => $employeeId) {
                $run->adjustments()->create([
                    'employee_id' => $employeeId,
                    'kind' => PayslipLine::EARNING,
                    'code' => 'SERVICE_CHARGE',
                    'label' => 'Service charge share',
                    'amount' => Money::ofCentavos($share + ($i < $remainder ? 1 : 0)),
                    'taxable' => true,
                    'created_by' => $request->user()?->id,
                ]);
            }

            $run->update(['status' => PayrollRunStatus::Draft]);
        });

        return back()->with('success', "Service charge of {$total->format()} split among {$employeeIds->count()} employee(s). Recompute the run to apply it.");
    }
}
