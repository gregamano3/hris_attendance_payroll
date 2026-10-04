<?php

namespace App\Features\Attendance\Api;

use App\Features\Attendance\Clock\RecordPunch;
use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AttendanceApiController
{
    private const MAX_RANGE_DAYS = 62;

    public function __construct(private EmployeeDirectory $directory) {}

    /**
     * Computed attendance days. Defaults to the caller's own record; others
     * need attendance.view or must be on the caller's team.
     */
    public function days(Request $request): JsonResponse
    {
        [$employee, $from, $to] = $this->scope($request);

        $days = AttendanceDay::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => $days->map(fn (AttendanceDay $d) => [
                'date' => $d->date->toDateString(),
                'status' => $d->status->value,
                'time_in' => $d->time_in?->toIso8601String(),
                'time_out' => $d->time_out?->toIso8601String(),
                'worked_minutes' => $d->worked_minutes,
                'late_minutes' => $d->late_minutes,
                'undertime_minutes' => $d->undertime_minutes,
                'overbreak_minutes' => $d->overbreak_minutes,
                'overtime_minutes' => $d->overtime_minutes,
                'night_diff_minutes' => $d->night_diff_minutes,
                'is_rest_day' => $d->is_rest_day,
                'holiday_type' => $d->holiday_type?->value,
                'leave_fraction' => (float) $d->leave_fraction,
            ])->values(),
            'meta' => ['employee_id' => $employee->id, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }

    public function timeLogs(Request $request): JsonResponse
    {
        [$employee, $from, $to] = $this->scope($request);

        $logs = TimeLog::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('logged_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('logged_at')
            ->get();

        return response()->json([
            'data' => $logs->map(fn (TimeLog $log) => $this->timeLog($log))->values(),
            'meta' => ['employee_id' => $employee->id, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }

    /**
     * Clock in/out for the caller, with the same branch restrictions as the web clock.
     */
    public function punch(Request $request, RecordPunch $punch): JsonResponse
    {
        $user = $this->user($request);
        $employee = $this->directory->forUser($user) ?? abort(403, 'Your account is not linked to an employee record.');

        $data = $request->validate([
            'type' => ['nullable', Rule::enum(TimeLogType::class)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ]);

        $log = $punch->handle(
            $employee,
            isset($data['type']) ? TimeLogType::from($data['type']) : 'main',
            $request->ip(),
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null,
            $user->id,
            TimeLogSource::Api,
        );

        return response()->json(['data' => $this->timeLog($log)], 201);
    }

    /**
     * @return array{0: Employee, 1: Carbon, 2: Carbon}
     */
    private function scope(Request $request): array
    {
        $user = $this->user($request);
        $data = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $own = $this->directory->forUser($user);
        $employee = isset($data['employee_id']) ? Employee::query()->findOrFail($data['employee_id']) : ($own ?? abort(403, 'Your account is not linked to an employee record.'));

        if ($employee->id !== $own?->id) {
            abort_unless($user->can('attendance.view') || $this->directory->teamIdsOf($user)->contains($employee->id), 403);
        }

        $to = Carbon::parse($data['to'] ?? today());
        $from = Carbon::parse($data['from'] ?? $to->copy()->subDays(14));
        abort_if($from->diffInDays($to) > self::MAX_RANGE_DAYS, 422, 'The date range may not exceed '.self::MAX_RANGE_DAYS.' days.');

        return [$employee, $from, $to];
    }

    private function user(Request $request): User
    {
        return $request->user() ?? abort(401);
    }

    /**
     * @return array<string, mixed>
     */
    private function timeLog(TimeLog $log): array
    {
        return [
            'id' => $log->id,
            'logged_at' => $log->logged_at->toIso8601String(),
            'type' => $log->type->value,
            'source' => $log->source->value,
        ];
    }
}
