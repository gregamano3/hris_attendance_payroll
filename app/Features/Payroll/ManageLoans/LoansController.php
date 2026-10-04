<?php

namespace App\Features\Payroll\ManageLoans;

use App\Features\Payroll\Enums\LoanStatus;
use App\Features\Payroll\Enums\LoanType;
use App\Features\Payroll\Models\Loan;
use App\Features\Payroll\Queries\EmployeeOptions;
use App\Shared\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoansController
{
    public function index(Request $request, EmployeeOptions $employees): View
    {
        $status = LoanStatus::tryFrom($request->string('status')->toString()) ?? LoanStatus::Active;

        return view('payroll::loans.index', [
            'loans' => Loan::query()->with('employee')->where('status', $status)->latest('starts_on')->paginate(25)->withQueryString(),
            'status' => $status,
            'employees' => $employees->active(),
            'types' => LoanType::options(),
        ]);
    }

    public function show(Loan $loan): View
    {
        return view('payroll::loans.show', ['loan' => $loan->load(['employee', 'payments.run'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'type' => ['required', Rule::enum(LoanType::class)],
            'reference_no' => ['nullable', 'string', 'max:50'],
            'principal' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'amortization' => ['required', 'numeric', 'gt:0', 'lte:principal'],
            'starts_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Loan::query()->create([
            ...$data,
            'balance' => Money::ofPesos($data['principal']),
            'status' => LoanStatus::Active,
        ]);

        return back()->with('success', 'Loan added. Amortizations are deducted on every payroll run from the start date.');
    }

    public function cancel(Loan $loan): RedirectResponse
    {
        if ($loan->status !== LoanStatus::Active) {
            return back()->with('error', 'Only active loans can be cancelled.');
        }

        $loan->update(['status' => LoanStatus::Cancelled]);

        return back()->with('success', 'Loan cancelled. No further amortizations will be deducted.');
    }
}
