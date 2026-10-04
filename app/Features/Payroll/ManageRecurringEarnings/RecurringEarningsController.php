<?php

namespace App\Features\Payroll\ManageRecurringEarnings;

use App\Features\Payroll\Enums\TaxTreatment;
use App\Features\Payroll\Models\DeMinimisBenefit;
use App\Features\Payroll\Models\RecurringEarning;
use App\Features\Payroll\Queries\EmployeeOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Allowances added automatically to every payroll run between their dates.
 */
class RecurringEarningsController
{
    public function index(Request $request, EmployeeOptions $employees): View
    {
        $employeeId = $request->integer('employee') ?: null;

        return view('payroll::allowances.index', [
            'earnings' => RecurringEarning::query()
                ->with('employee')
                ->when($employeeId, fn ($q, $id) => $q->where('employee_id', $id))
                ->orderByDesc('starts_on')
                ->paginate(25)
                ->withQueryString(),
            'employees' => $employees->active(),
            'employeeId' => $employeeId,
            'treatments' => TaxTreatment::options(),
            'benefits' => DeMinimisBenefit::query()->orderBy('name')->get()->mapWithKeys(fn (DeMinimisBenefit $b): array => [
                $b->id => "{$b->name} (₱".number_format($b->limit_amount->toFloat(), 2).' / '.($b->period === 'monthly' ? 'month' : 'year').')',
            ])->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'label' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'tax_treatment' => ['required', Rule::enum(TaxTreatment::class)],
            'de_minimis_benefit_id' => ['nullable', 'required_if:tax_treatment,de_minimis', 'integer', Rule::exists('de_minimis_benefits', 'id')],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        if ($data['tax_treatment'] !== TaxTreatment::DeMinimis->value) {
            $data['de_minimis_benefit_id'] = null;
        }

        RecurringEarning::query()->create($data);

        return back()->with('success', 'Recurring allowance added. It applies to runs computed from now on.');
    }

    public function update(Request $request, RecurringEarning $recurringEarning): RedirectResponse
    {
        $data = $request->validate(['ends_on' => ['required', 'date', 'after_or_equal:'.$recurringEarning->starts_on->toDateString()]]);

        $recurringEarning->update($data);

        return back()->with('success', 'Recurring allowance end date set.');
    }

    public function destroy(RecurringEarning $recurringEarning): RedirectResponse
    {
        $recurringEarning->delete();

        return back()->with('success', 'Recurring allowance removed.');
    }
}
