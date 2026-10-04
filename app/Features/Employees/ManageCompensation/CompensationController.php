<?php

namespace App\Features\Employees\ManageCompensation;

use App\Features\Employees\Enums\RateType;
use App\Features\Employees\Models\CompensationChange;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\CompensationHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Effective-dated salary changes (increases, promotions, corrections),
 * including future-dated and retroactive ones.
 */
class CompensationController
{
    public function __construct(private CompensationHistory $history) {}

    public function index(Employee $employee): View
    {
        $lastFinalizedPeriodEnd = DB::table('payroll_runs')
            ->join('payslips', 'payslips.payroll_run_id', '=', 'payroll_runs.id')
            ->where('payslips.employee_id', $employee->id)
            ->where('payroll_runs.status', 'finalized')
            ->where('payroll_runs.type', 'regular')
            ->max('payroll_runs.period_end');

        return view('employees::compensation.index', [
            'employee' => $employee->load('compensationChanges.creator'),
            'rateTypes' => RateType::options(),
            'lastFinalizedPeriodEnd' => $lastFinalizedPeriodEnd ? Carbon::parse($lastFinalizedPeriodEnd) : null,
        ]);
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'effective_from' => ['required', 'date', 'after_or_equal:'.$employee->hired_at->toDateString(),
                Rule::unique('compensation_changes')->where('employee_id', $employee->id)],
            'rate_type' => ['required', Rule::enum(RateType::class)],
            'basic_rate' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $change = $employee->compensationChanges()->create([...$data, 'created_by' => $request->user()?->id]);
        $this->history->syncCurrent($employee);

        $message = $change->effective_from->isFuture()
            ? "Rate change scheduled for {$change->effective_from->format('M j, Y')}."
            : 'Rate change recorded.';

        return back()->with('success', $message);
    }

    public function destroy(Employee $employee, CompensationChange $change): RedirectResponse
    {
        abort_unless($change->employee_id === $employee->id, 404);

        if ($employee->compensationChanges()->count() === 1) {
            return back()->with('error', 'The only rate on record cannot be removed.');
        }

        $change->delete();
        $this->history->syncCurrent($employee);

        return back()->with('success', 'Rate change removed.');
    }
}
