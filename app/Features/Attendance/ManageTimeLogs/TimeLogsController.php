<?php

namespace App\Features\Attendance\ManageTimeLogs;

use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Shared\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TimeLogsController
{
    public function index(Request $request): View
    {
        $period = Period::fromRequest($request, today());
        $employeeId = $request->integer('employee') ?: null;

        $logs = TimeLog::withTrashed()
            ->with(['employee', 'creator'])
            ->when($employeeId, fn ($q, $id) => $q->where('employee_id', $id))
            ->whereBetween('logged_at', [$period->from, $period->to->copy()->endOfDay()])
            ->latest('logged_at')
            ->paginate(30)
            ->withQueryString();

        return view('attendance::time-logs.index', [
            'logs' => $logs,
            'period' => $period,
            'employeeId' => $employeeId,
            'employees' => $this->employees(),
            'types' => TimeLogType::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'logged_at' => ['required', 'date', 'before_or_equal:now'],
            'type' => ['required', Rule::enum(TimeLogType::class)],
            'remarks' => ['required', 'string', 'max:255'],
        ]);

        TimeLog::query()->create([
            ...$data,
            'source' => TimeLogSource::Manual,
            'created_by' => $request->user()?->id,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Time log added.');
    }

    public function destroy(Request $request, TimeLog $timeLog): RedirectResponse
    {
        $timeLog->forceFill(['deleted_by' => $request->user()?->id])->save();
        $timeLog->delete();

        return back()->with('success', 'Time log removed. It is kept in the audit trail.');
    }

    /**
     * @return array<int, string>
     */
    private function employees(): array
    {
        return Employee::query()->orderBy('last_name')->orderBy('first_name')->get()
            ->mapWithKeys(fn (Employee $e): array => [$e->id => "{$e->employee_no} — {$e->full_name}"])
            ->all();
    }
}
