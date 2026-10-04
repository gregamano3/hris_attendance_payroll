<?php

namespace App\Features\Payroll\CreatePayrollRun;

use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollRun;
use App\Shared\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreatePayrollRunController
{
    public function create(): View
    {
        $latest = PayrollRun::query()->latest('period_end')->first();
        $period = Period::semiMonthlyContaining($latest ? $latest->period_end->copy()->addDay() : today());

        return view('payroll::runs.create', [
            'period' => $period,
            'payDate' => $period->to,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:'.Carbon::parse((string) $request->input('period_start'))->addDays(31)->toDateString()],
            'pay_date' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $overlap = PayrollRun::query()
            ->whereDate('period_start', '<=', $data['period_end'])
            ->whereDate('period_end', '>=', $data['period_start'])
            ->exists();

        $request->validate(['period_start' => [Rule::prohibitedIf($overlap)]], [
            'period_start.prohibited' => 'Another payroll run already covers part of this period.',
        ]);

        $period = new Period(Carbon::parse($data['period_start']), Carbon::parse($data['period_end']));

        $run = PayrollRun::query()->create([
            ...$data,
            'name' => 'Payroll '.$period->label(),
            'status' => PayrollRunStatus::Draft,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('payroll.runs.show', $run)->with('success', 'Payroll run created. Compute it to generate payslips.');
    }
}
