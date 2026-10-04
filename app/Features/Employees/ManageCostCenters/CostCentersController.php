<?php

namespace App\Features\Employees\ManageCostCenters;

use App\Features\Employees\Models\CostCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CostCentersController
{
    public function index(): View
    {
        return view('employees::cost-centers.index', ['costCenters' => CostCenter::query()->withCount('employees')->orderBy('code')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('cost_centers')],
            'name' => ['required', 'string', 'max:255', Rule::unique('cost_centers')],
        ]);

        CostCenter::query()->create([...$data, 'code' => strtoupper($data['code'])]);

        return back()->with('success', 'Cost center added.');
    }

    public function destroy(CostCenter $costCenter): RedirectResponse
    {
        if ($costCenter->employees()->withTrashed()->exists()) {
            return back()->with('error', 'Cost centers with employees cannot be deleted.');
        }

        $costCenter->delete();

        return back()->with('success', 'Cost center deleted.');
    }
}
