<?php

namespace App\Features\Payroll\ManageDeMinimis;

use App\Features\Payroll\Models\DeMinimisBenefit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeMinimisController
{
    public function index(): View
    {
        return view('payroll::de-minimis', ['benefits' => DeMinimisBenefit::query()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('de_minimis_benefits')],
            'name' => ['required', 'string', 'max:255'],
            'limit_amount' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'period' => ['required', Rule::in(['monthly', 'annual'])],
        ]);

        DeMinimisBenefit::query()->create([...$data, 'code' => strtoupper($data['code'])]);

        return back()->with('success', 'De minimis benefit added.');
    }

    public function update(Request $request, DeMinimisBenefit $benefit): RedirectResponse
    {
        $benefit->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'limit_amount' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'period' => ['required', Rule::in(['monthly', 'annual'])],
        ]));

        return back()->with('success', "{$benefit->name} updated.");
    }
}
