<?php

namespace App\Features\Attendance\ManageShifts;

use App\Features\Attendance\Models\EmployeeShift;
use App\Features\Attendance\Models\Shift;
use App\Features\Employees\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShiftsController
{
    public function index(): View
    {
        $shifts = Shift::query()->withCount('assignments')->orderBy('start_time')->get();

        $assignments = EmployeeShift::query()
            ->with(['employee', 'shift'])
            ->whereHas('employee')
            ->latest('effective_from')
            ->limit(15)
            ->get();

        return view('attendance::shifts.index', [
            'shifts' => $shifts,
            'assignments' => $assignments,
            'employees' => Employee::query()->active()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('attendance::shifts.form', ['shift' => new Shift([
            'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60, 'grace_minutes' => 5, 'work_days' => [1, 2, 3, 4, 5],
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shift = DB::transaction(fn () => $this->save(new Shift, $this->validated($request)));

        return redirect()->route('shifts.index')->with('success', "Shift {$shift->name} created.");
    }

    public function edit(Shift $shift): View
    {
        return view('attendance::shifts.form', compact('shift'));
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        DB::transaction(fn () => $this->save($shift, $this->validated($request, $shift)));

        return redirect()->route('shifts.index')->with('success', "Shift {$shift->name} updated.");
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        if ($shift->assignments()->exists()) {
            return back()->with('error', 'Shifts assigned to employees cannot be deleted.');
        }

        $shift->delete();

        return redirect()->route('shifts.index')->with('success', "Shift {$shift->name} deleted.");
    }

    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shift_id' => ['required', 'integer', Rule::exists('shifts', 'id')],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', Rule::exists('employees', 'id')],
            'effective_from' => ['required', 'date'],
        ]);

        foreach ($data['employee_ids'] as $employeeId) {
            EmployeeShift::query()->updateOrCreate(
                ['employee_id' => $employeeId, 'effective_from' => $data['effective_from']],
                ['shift_id' => $data['shift_id']],
            );
        }

        return back()->with('success', count($data['employee_ids']).' employee(s) assigned.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Shift $shift = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('shifts')->ignore($shift)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', 'between:1,7'],
            'is_default' => ['boolean'],
            'is_flexible' => ['boolean'],
            'core_start' => ['nullable', 'required_if:is_flexible,1', 'date_format:H:i'],
            'core_end' => ['nullable', 'required_if:is_flexible,1', 'date_format:H:i', 'after:core_start'],
            'required_hours' => ['nullable', 'required_if:is_flexible,1', 'numeric', 'min:1', 'max:16'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(Shift $shift, array $data): Shift
    {
        $data['work_days'] = array_values(array_map('intval', $data['work_days']));
        $data['is_default'] = (bool) ($data['is_default'] ?? false);
        $data['is_flexible'] = (bool) ($data['is_flexible'] ?? false);
        $data['required_minutes'] = $data['is_flexible'] ? (int) round((float) $data['required_hours'] * 60) : null;
        unset($data['required_hours']);

        if (! $data['is_flexible']) {
            $data['core_start'] = $data['core_end'] = null;
        }

        if ($data['is_default']) {
            Shift::query()->whereKeyNot($shift->id ?? 0)->update(['is_default' => false]);
        }

        $shift->fill($data)->save();

        return $shift;
    }
}
