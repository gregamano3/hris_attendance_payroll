<?php

namespace App\Features\Payroll\ProcessPayrollRun;

use App\Features\Payroll\Compute\ComputePayrollRun;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Lifecycle transitions of a run: compute, finalize and delete.
 */
class ProcessPayrollRunController
{
    public function compute(PayrollRun $run, ComputePayrollRun $compute): RedirectResponse
    {
        if ($run->isLocked()) {
            return back()->with('error', 'Finalized payroll runs cannot be recomputed.');
        }

        try {
            $run = $compute->handle($run);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Computation failed: '.$e->getMessage());
        }

        return back()->with('success', "Computed {$run->employee_count} payslip(s).");
    }

    public function finalize(Request $request, PayrollRun $run): RedirectResponse
    {
        if ($run->status !== PayrollRunStatus::Computed) {
            return back()->with('error', 'Only computed runs with up-to-date payslips can be finalized.');
        }

        $run->update([
            'status' => PayrollRunStatus::Finalized,
            'finalized_by' => $request->user()?->id,
            'finalized_at' => now(),
        ]);

        return back()->with('success', 'Payroll finalized. Payslips are now visible to employees.');
    }

    public function destroy(PayrollRun $run): RedirectResponse
    {
        if ($run->isLocked()) {
            return back()->with('error', 'Finalized payroll runs cannot be deleted.');
        }

        $run->delete();

        return redirect()->route('payroll.runs.index')->with('success', 'Payroll run deleted.');
    }
}
