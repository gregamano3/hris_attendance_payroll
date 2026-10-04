<?php

namespace App\Features\Payroll\ManageFinalPay;

use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Compute\ComputeFinalPay;
use App\Features\Payroll\Compute\PayslipLine;
use App\Features\Payroll\Enums\LoanStatus;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\FinalPay;
use App\Features\Payroll\Models\FinalPayAdjustment;
use App\Features\Payroll\Models\Loan;
use App\Features\Payroll\Models\LoanPayment;
use App\Shared\Money\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FinalPayController
{
    public function index(): View
    {
        return view('payroll::final-pay.index', [
            'finalPays' => FinalPay::query()->with('employee')->latest('separation_date')->get(),
            'pending' => Employee::withTrashed()
                ->whereIn('status', [EmploymentStatus::Resigned, EmploymentStatus::Terminated])
                ->whereNotNull('separated_at')
                ->whereDoesntHave('finalPay')
                ->orderByDesc('separated_at')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id'), Rule::unique('final_pays', 'employee_id')],
        ]);

        $employee = Employee::withTrashed()->findOrFail($data['employee_id']);

        if ($employee->separated_at === null) {
            return back()->with('error', 'Set the separation date on the employee record first.');
        }

        $finalPay = FinalPay::query()->create([
            'employee_id' => $employee->id,
            'separation_date' => $employee->separated_at,
            'status' => PayrollRunStatus::Draft,
        ]);

        return redirect()->route('payroll.final-pay.show', $finalPay)->with('success', 'Final pay created. Add any adjustments, then compute it.');
    }

    public function show(FinalPay $finalPay): View
    {
        return view('payroll::final-pay.show', ['finalPay' => $finalPay->load(['employee', 'adjustments', 'finalizer'])]);
    }

    public function compute(FinalPay $finalPay, ComputeFinalPay $compute): RedirectResponse
    {
        if ($finalPay->isLocked()) {
            return back()->with('error', 'Finalized final pay cannot be recomputed.');
        }

        $compute->handle($finalPay);

        return back()->with('success', 'Final pay computed.');
    }

    public function storeAdjustment(Request $request, FinalPay $finalPay): RedirectResponse
    {
        abort_if($finalPay->isLocked(), 409);

        $data = $request->validate([
            'kind' => ['required', Rule::in([PayslipLine::EARNING, PayslipLine::DEDUCTION])],
            'label' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'taxable' => ['boolean'],
        ]);

        $finalPay->adjustments()->create([...$data, 'taxable' => $data['kind'] === PayslipLine::EARNING && $request->boolean('taxable')]);
        $finalPay->update(['status' => PayrollRunStatus::Draft]);

        return back()->with('success', 'Adjustment added. Recompute to apply it.');
    }

    public function destroyAdjustment(FinalPay $finalPay, FinalPayAdjustment $adjustment): RedirectResponse
    {
        abort_if($finalPay->isLocked() || $adjustment->final_pay_id !== $finalPay->id, 409);

        $adjustment->delete();
        $finalPay->update(['status' => PayrollRunStatus::Draft]);

        return back()->with('success', 'Adjustment removed. Recompute to apply it.');
    }

    public function finalize(Request $request, FinalPay $finalPay): RedirectResponse
    {
        if ($finalPay->status !== PayrollRunStatus::Computed) {
            return back()->with('error', 'Compute the final pay (after the last adjustments) before finalizing it.');
        }

        DB::transaction(function () use ($request, $finalPay) {
            // Outstanding loans are settled by the final pay.
            Loan::query()->where('employee_id', $finalPay->employee_id)->where('status', LoanStatus::Active)->where('balance', '>', 0)
                ->lockForUpdate()->get()
                ->each(function (Loan $loan) use ($finalPay) {
                    LoanPayment::query()->create([
                        'loan_id' => $loan->id, 'final_pay_id' => $finalPay->id, 'amount' => $loan->balance, 'balance_after' => Money::zero(),
                    ]);
                    $loan->update(['balance' => Money::zero(), 'status' => LoanStatus::Paid]);
                });

            $finalPay->update([
                'status' => PayrollRunStatus::Finalized,
                'finalized_by' => $request->user()?->id,
                'finalized_at' => now(),
            ]);
        });

        return back()->with('success', 'Final pay finalized. Outstanding loans were settled.');
    }

    public function destroy(FinalPay $finalPay): RedirectResponse
    {
        if ($finalPay->isLocked()) {
            return back()->with('error', 'Finalized final pay cannot be deleted.');
        }

        $finalPay->delete();

        return redirect()->route('payroll.final-pay.index')->with('success', 'Final pay deleted.');
    }

    public function pdf(FinalPay $finalPay): Response
    {
        abort_if($finalPay->lines === null, 404);

        return Pdf::loadView('payroll::final-pay.pdf', ['finalPay' => $finalPay->load('employee')])
            ->setPaper('a4')
            ->download("final-pay-{$finalPay->employee->employee_no}.pdf");
    }
}
