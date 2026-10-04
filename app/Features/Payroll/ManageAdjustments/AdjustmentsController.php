<?php

namespace App\Features\Payroll\ManageAdjustments;

use App\Features\Payroll\Compute\PayslipLine;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollAdjustment;
use App\Features\Payroll\Models\PayrollRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * One-off allowances and deductions. Changing them puts the run back to
 * draft so it has to be recomputed before it can be finalized.
 */
class AdjustmentsController
{
    public function store(Request $request, PayrollRun $run): RedirectResponse
    {
        abort_if($run->isLocked() || $run->isComputing(), 409);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'kind' => ['required', Rule::in([PayslipLine::EARNING, PayslipLine::DEDUCTION])],
            'label' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'taxable' => ['boolean'],
        ]);

        $run->adjustments()->create([
            ...$data,
            'taxable' => $data['kind'] === PayslipLine::EARNING && $request->boolean('taxable'),
            'created_by' => $request->user()?->id,
        ]);
        $run->update(['status' => PayrollRunStatus::Draft]);

        return back()->with('success', 'Adjustment added. Recompute the run to apply it.');
    }

    public function destroy(PayrollRun $run, PayrollAdjustment $adjustment): RedirectResponse
    {
        abort_if($run->isLocked() || $run->isComputing() || $adjustment->payroll_run_id !== $run->id, 409);

        $adjustment->delete();
        $run->update(['status' => PayrollRunStatus::Draft]);

        return back()->with('success', 'Adjustment removed. Recompute the run to apply it.');
    }
}
