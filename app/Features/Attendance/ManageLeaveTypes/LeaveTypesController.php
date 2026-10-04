<?php

namespace App\Features\Attendance\ManageLeaveTypes;

use App\Features\Attendance\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveTypesController
{
    public function index(): View
    {
        return view('attendance::leave-types.index', ['types' => LeaveType::query()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        LeaveType::query()->create($this->validated($request));

        return back()->with('success', 'Leave type added.');
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($this->validated($request, $leaveType));

        return back()->with('success', "{$leaveType->name} updated.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?LeaveType $type = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'alpha_dash', Rule::unique('leave_types')->ignore($type)],
            'name' => ['required', 'string', 'max:100'],
            'days_per_year' => ['required', 'integer', 'min:0', 'max:366'],
            'accrual_per_month' => ['required', 'numeric', 'min:0', 'max:31'],
            'carry_over_cap' => ['required', 'integer', 'min:0', 'max:366'],
            'is_paid' => ['boolean'],
            'is_convertible' => ['boolean'],
        ]);

        return [...$data, 'code' => strtoupper($data['code']), 'is_paid' => $request->boolean('is_paid'), 'is_convertible' => $request->boolean('is_convertible')];
    }
}
