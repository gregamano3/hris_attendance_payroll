<?php

namespace App\Features\Payroll\ProcessPayrollRun;

use App\Features\Payroll\Compute\ApplyLoanPayments;
use App\Features\Payroll\Compute\ComputePayrollRunJob;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Notifications\PayslipReleased;
use App\Shared\Notifications\Recipients;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Lifecycle transitions of a run: compute, finalize and delete.
 */
class ProcessPayrollRunController
{
    public function compute(PayrollRun $run): RedirectResponse
    {
        if ($run->isLocked()) {
            return back()->with('error', 'Finalized payroll runs cannot be recomputed.');
        }

        // Atomically claim the run so double submits never queue two jobs.
        $claimed = PayrollRun::query()
            ->whereKey($run->id)
            ->where('status', '!=', PayrollRunStatus::Computing)
            ->where('status', '!=', PayrollRunStatus::Finalized)
            ->update(['status' => PayrollRunStatus::Computing, 'progress' => 0, 'compute_error' => null]);

        if ($claimed === 0) {
            return back()->with('warning', 'This payroll run is already being computed.');
        }

        try {
            ComputePayrollRunJob::dispatch($run->fresh());
        } catch (Throwable $e) {
            // Only reached with the synchronous queue driver; the job's failed() hook already stored the error.
            report($e);
        }

        $run->refresh();

        return back()->with(...match ($run->status) {
            PayrollRunStatus::Computed => ['success', "Computed {$run->employee_count} payslip(s)."],
            PayrollRunStatus::Computing => ['status', 'Computing payslips in the background…'],
            default => ['error', 'Computation failed: '.$run->compute_error],
        });
    }

    /**
     * Polled by the run page while a computation is running.
     */
    public function status(PayrollRun $run): JsonResponse
    {
        return response()->json([
            'status' => $run->status->value,
            'progress' => $run->progress,
            'error' => $run->compute_error,
        ]);
    }

    public function finalize(Request $request, PayrollRun $run, ApplyLoanPayments $loans): RedirectResponse
    {
        if ($run->status !== PayrollRunStatus::Computed) {
            return back()->with('error', 'Only computed runs with up-to-date payslips can be finalized.');
        }

        DB::transaction(function () use ($request, $run, $loans) {
            $run->update([
                'status' => PayrollRunStatus::Finalized,
                'finalized_by' => $request->user()?->id,
                'finalized_at' => now(),
            ]);

            $loans->handle($run);
        });

        foreach ($run->payslips()->with('employee.user')->get() as $payslip) {
            Recipients::active($payslip->employee->user)?->notify(new PayslipReleased($payslip));
        }

        return back()->with('success', 'Payroll finalized. Payslips are now visible to employees.');
    }

    public function destroy(PayrollRun $run): RedirectResponse
    {
        if ($run->isLocked() || $run->isComputing()) {
            return back()->with('error', 'Finalized or computing payroll runs cannot be deleted.');
        }

        $run->delete();

        return redirect()->route('payroll.runs.index')->with('success', 'Payroll run deleted.');
    }
}
