<?php

namespace App\Features\Attendance\ManageHolidays;

use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Models\Holiday;
use App\Features\Employees\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidaysController
{
    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: today()->year;

        return view('attendance::holidays.index', [
            'year' => $year,
            'holidays' => Holiday::query()->with('branch')->whereYear('date', $year)->orderBy('date')->get(),
            'types' => HolidayType::options(),
            'branches' => Branch::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $holiday = Holiday::query()->create($this->validated($request));

        return redirect()->route('holidays.index', ['year' => $holiday->date->year])->with('success', "{$holiday->name} added.");
    }

    public function edit(Holiday $holiday): View
    {
        return view('attendance::holidays.edit', [
            'holiday' => $holiday,
            'types' => HolidayType::options(),
            'branches' => Branch::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($this->validated($request, $holiday));

        return redirect()->route('holidays.index', ['year' => $holiday->date->year])->with('success', "{$holiday->name} updated.");
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('success', "{$holiday->name} removed.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Holiday $holiday = null): array
    {
        $branchId = $request->integer('branch_id') ?: null;

        return $request->validate([
            'date' => ['required', 'date', Rule::unique('holidays')->ignore($holiday)
                ->where(fn ($q) => $branchId === null ? $q->whereNull('branch_id') : $q->where('branch_id', $branchId))],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(HolidayType::class)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
        ]);
    }
}
