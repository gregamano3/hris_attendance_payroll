<?php

namespace App\Features\Payroll\ComputeBackPay;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Models\PayrollRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BackPayController
{
    public function __invoke(Request $request, PayrollRun $run, ComputeBackPay $backPay): RedirectResponse
    {
        if ($run->isLocked() || $run->isThirteenthMonth()) {
            return back()->with('error', 'Back pay can only be added to a draft or computed regular run.');
        }

        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'since' => ['required', 'date', 'before:'.$run->period_start->toDateString()],
        ]);

        $created = $backPay->handle($run, Employee::withTrashed()->findOrFail($data['employee_id']), Carbon::parse($data['since']), $request->user()?->id);

        return back()->with($created->isEmpty() ? 'warning' : 'success', $created->isEmpty()
            ? 'No differences found for finalized runs since that date.'
            : "Added {$created->count()} back pay adjustment(s). Recompute the run to apply them.");
    }
}
