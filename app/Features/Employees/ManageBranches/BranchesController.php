<?php

namespace App\Features\Employees\ManageBranches;

use App\Features\Employees\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchesController
{
    public function index(): View
    {
        return view('employees::branches.index', ['branches' => Branch::query()->withCount('employees')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('employees::branches.form', ['branch' => new Branch]);
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = Branch::query()->create($this->validated($request));

        return redirect()->route('branches.index')->with('success', "Branch {$branch->name} created.");
    }

    public function edit(Branch $branch): View
    {
        return view('employees::branches.form', compact('branch'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validated($request, $branch));

        return redirect()->route('branches.index')->with('success', "Branch {$branch->name} updated.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->employees()->withTrashed()->exists()) {
            return back()->with('error', 'Branches with employees cannot be deleted.');
        }

        $branch->delete();

        return redirect()->route('branches.index')->with('success', "Branch {$branch->name} deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Branch $branch = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('branches')->ignore($branch)],
            'name' => ['required', 'string', 'max:255', Rule::unique('branches')->ignore($branch)],
            'address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'geofence_radius_m' => ['nullable', 'integer', 'min:20', 'max:100000', 'required_with:latitude'],
            'allowed_ip_ranges' => ['nullable', 'string', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                foreach (array_filter(array_map('trim', explode(',', (string) $value))) as $range) {
                    [$ip, $bits] = array_pad(explode('/', $range, 2), 2, null);

                    if (filter_var($ip, FILTER_VALIDATE_IP) === false || ($bits !== null && (! ctype_digit($bits) || (int) $bits > (str_contains($ip, ':') ? 128 : 32)))) {
                        $fail("[{$range}] is not a valid IP address or CIDR range.");
                    }
                }
            }],
        ]);

        return [...$data, 'code' => strtoupper($data['code'])];
    }
}
