<?php

namespace App\Features\Attendance\ManageRoster;

use App\Features\Attendance\Models\RosterEntry;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Queries\ShiftResolver;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Weekly roster: override the assigned shift or make a rest day for specific
 * dates (rotating shifts, store schedules, swaps).
 */
class RosterController
{
    public function index(Request $request, ShiftResolver $resolver): View
    {
        $weekStart = rescue(fn () => Carbon::parse($request->string('week')->toString() ?: 'now'), today(), false)->startOfWeek();
        $days = collect(range(0, 6))->map(fn (int $i) => $weekStart->copy()->addDays($i));
        $departmentId = $request->integer('department') ?: null;

        $employees = Employee::query()->active()
            ->when($departmentId, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('last_name')->orderBy('first_name')->limit(100)->get();

        $entries = RosterEntry::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$weekStart->toDateString(), $weekStart->copy()->addDays(6)->toDateString()])
            ->get()
            ->keyBy(fn (RosterEntry $e) => $e->employee_id.'|'.$e->date->toDateString());

        return view('attendance::roster', [
            'weekStart' => $weekStart,
            'days' => $days,
            'employees' => $employees,
            'entries' => $entries,
            'defaults' => $employees->mapWithKeys(fn (Employee $e) => [$e->id => $days->mapWithKeys(fn (Carbon $d) => [$d->toDateString() => $this->describe($resolver, $e->id, $d)])]),
            'shifts' => Shift::query()->orderBy('start_time')->get(),
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'departmentId' => $departmentId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cells' => ['required', 'array'],
            'cells.*' => ['array'],
            'cells.*.*' => ['nullable', 'string', Rule::in(['', 'rest', ...Shift::query()->pluck('id')->map(fn ($id) => (string) $id)->all()])],
        ]);

        $changed = 0;

        DB::transaction(function () use ($data, &$changed) {
            foreach ($data['cells'] as $employeeId => $dates) {
                foreach ($dates as $date => $value) {
                    $existing = RosterEntry::query()->where('employee_id', $employeeId)->whereDate('date', $date)->first();
                    $value = (string) $value;

                    if ($value === '') {
                        if ($existing) {
                            $existing->delete();
                            $changed++;
                        }

                        continue;
                    }

                    $attributes = $value === 'rest' ? ['shift_id' => null, 'is_rest_day' => true] : ['shift_id' => (int) $value, 'is_rest_day' => false];

                    if ($existing === null || $existing->is_rest_day !== $attributes['is_rest_day'] || $existing->shift_id !== $attributes['shift_id']) {
                        RosterEntry::query()->updateOrCreate(['employee_id' => (int) $employeeId, 'date' => $date], $attributes);
                        $changed++;
                    }
                }
            }
        });

        return back()->with('success', "Roster saved ({$changed} change(s)).");
    }

    private function describe(ShiftResolver $resolver, int $employeeId, Carbon $date): string
    {
        // What applies without a roster entry.
        $entry = RosterEntry::query()->where('employee_id', $employeeId)->whereDate('date', $date)->exists();
        $shift = $entry ? null : $resolver->forEmployee($employeeId, $date);

        if ($shift === null) {
            return '';
        }

        return $shift->isWorkDay($date) ? substr((string) $shift->start_time, 0, 5).'–'.substr((string) $shift->end_time, 0, 5) : 'Rest';
    }
}
