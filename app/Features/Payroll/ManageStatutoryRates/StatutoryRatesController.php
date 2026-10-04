<?php

namespace App\Features\Payroll\ManageStatutoryRates;

use App\Features\Payroll\Enums\StatutoryScheme;
use App\Features\Payroll\Models\StatutoryRate;
use App\Features\Payroll\Models\TaxBracket;
use App\Shared\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Statutory parameters are never edited in place: a new effective-dated
 * version is added so past payroll runs stay reproducible.
 */
class StatutoryRatesController
{
    public function index(): View
    {
        return view('payroll::statutory.index', [
            'schemes' => StatutoryScheme::cases(),
            'rates' => StatutoryRate::query()->orderByDesc('effective_from')->get()->groupBy(fn (StatutoryRate $r) => $r->scheme->value),
            'taxTables' => TaxBracket::query()->orderByDesc('effective_from')->orderBy('lower_bound')->get()
                ->groupBy(fn (TaxBracket $b) => $b->frequency.'|'.$b->effective_from->toDateString()),
        ]);
    }

    public function storeRate(Request $request): RedirectResponse
    {
        $scheme = StatutoryScheme::tryFrom($request->string('scheme')->toString())
            ?? throw ValidationException::withMessages(['scheme' => 'Unknown scheme.']);

        $rules = [
            'effective_from' => ['required', 'date', Rule::unique('statutory_rates')->where('scheme', $scheme->value)],
        ];

        foreach (array_keys($scheme->parameters()) as $key) {
            $rules["parameters.{$key}"] = ['required', 'numeric', 'min:0'];
        }

        $data = $request->validate($rules);

        StatutoryRate::query()->create([
            'scheme' => $scheme,
            'effective_from' => $data['effective_from'],
            'parameters' => array_map('floatval', $data['parameters']),
        ]);

        return back()->with('success', "New {$scheme->label()} rates saved.");
    }

    public function storeTaxTable(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'frequency' => ['required', Rule::in(['semi_monthly', 'monthly'])],
            'effective_from' => ['required', 'date'],
            'brackets' => ['required', 'array'],
            'brackets.*.lower' => ['nullable', 'numeric', 'min:0'],
            'brackets.*.upper' => ['nullable', 'numeric', 'min:0'],
            'brackets.*.base' => ['nullable', 'numeric', 'min:0'],
            'brackets.*.rate' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $brackets = array_values(array_filter($data['brackets'], fn (array $b) => ($b['lower'] ?? '') !== ''));

        if ($brackets === []) {
            throw ValidationException::withMessages(['brackets' => 'Enter at least one bracket.']);
        }

        $exists = TaxBracket::query()->where('frequency', $data['frequency'])->whereDate('effective_from', $data['effective_from'])->exists();

        if ($exists) {
            throw ValidationException::withMessages(['effective_from' => 'A table with this frequency and date already exists.']);
        }

        DB::transaction(function () use ($brackets, $data) {
            foreach ($brackets as $bracket) {
                TaxBracket::query()->create([
                    'effective_from' => $data['effective_from'],
                    'frequency' => $data['frequency'],
                    'lower_bound' => Money::ofPesos($bracket['lower']),
                    'upper_bound' => ($bracket['upper'] ?? '') === '' ? null : Money::ofPesos($bracket['upper']),
                    'base_tax' => Money::ofPesos($bracket['base'] ?? 0),
                    'rate' => (float) ($bracket['rate'] ?? 0),
                ]);
            }
        });

        return back()->with('success', 'New withholding tax table saved.');
    }
}
