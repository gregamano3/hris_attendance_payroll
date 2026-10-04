<?php

namespace App\Features\Payroll\CreatePayrollRun;

use App\Features\Payroll\Enums\PayFrequency;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
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
        $latest = PayrollRun::query()->where('type', PayrollRunType::Regular)->latest('period_end')->first();
        $period = Period::semiMonthlyContaining($latest ? $latest->period_end->copy()->addDay() : today());

        return view('payroll::runs.create', [
            'period' => $period,
            'payDate' => $period->to,
            'types' => PayrollRunType::options(),
            'frequencies' => PayFrequency::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('type') === PayrollRunType::ThirteenthMonth->value) {
            return $this->storeThirteenthMonth($request);
        }

        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:'.Carbon::parse((string) $request->input('period_start'))->addDays(31)->toDateString()],
            'pay_date' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'annualize_tax' => ['boolean'],
            'frequency' => ['nullable', Rule::enum(PayFrequency::class)],
        ]);
        $data['annualize_tax'] = $request->boolean('annualize_tax');
        $data['frequency'] = PayFrequency::tryFrom((string) ($data['frequency'] ?? '')) ?? PayFrequency::SemiMonthly;

        $overlap = PayrollRun::query()
            ->where('type', PayrollRunType::Regular)
            ->where('frequency', $data['frequency'])
            ->whereDate('period_start', '<=', $data['period_end'])
            ->whereDate('period_end', '>=', $data['period_start'])
            ->exists();

        $request->validate(['period_start' => [Rule::prohibitedIf($overlap)]], [
            'period_start.prohibited' => 'Another payroll run already covers part of this period.',
        ]);

        $period = new Period(Carbon::parse($data['period_start']), Carbon::parse($data['period_end']));

        $run = PayrollRun::query()->create([
            ...$data,
            'name' => ($data['frequency'] === PayFrequency::SemiMonthly ? 'Payroll ' : $data['frequency']->label().' payroll ').$period->label(),
            'type' => PayrollRunType::Regular,
            'status' => PayrollRunStatus::Draft,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('payroll.runs.show', $run)->with('success', 'Payroll run created. Compute it to generate payslips.');
    }

    /**
     * One 13th month run per calendar year, covering January to December.
     */
    private function storeThirteenthMonth(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'pay_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $exists = PayrollRun::query()
            ->where('type', PayrollRunType::ThirteenthMonth)
            ->whereYear('period_end', $data['year'])
            ->exists();

        $request->validate(['year' => [Rule::prohibitedIf($exists)]], [
            'year.prohibited' => 'A 13th month run already exists for this year.',
        ]);

        $run = PayrollRun::query()->create([
            'name' => "13th month pay {$data['year']}",
            'type' => PayrollRunType::ThirteenthMonth,
            'period_start' => "{$data['year']}-01-01",
            'period_end' => "{$data['year']}-12-31",
            'pay_date' => $data['pay_date'],
            'notes' => $data['notes'] ?? null,
            'status' => PayrollRunStatus::Draft,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('payroll.runs.show', $run)->with('success', '13th month run created. Compute it to generate payslips.');
    }
}
