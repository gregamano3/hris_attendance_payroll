<?php

namespace App\Features\Attendance\DeviceApi;

use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\AttendanceDevice;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * POST /api/attendance/punches — Authorization: Bearer <device token>
 * {"punches": [{"employee_no": "EMP-00001", "timestamp": "2026-10-05T07:58:00+08:00", "type": "in"}]}
 *
 * Idempotent: an identical punch (employee, time, type) is reported as a
 * duplicate, so devices can safely retry.
 */
class DevicePunchesController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'punches' => ['required', 'array', 'min:1', 'max:500'],
            'punches.*.employee_no' => ['required', 'string', 'max:30'],
            'punches.*.timestamp' => ['required', 'date'],
            'punches.*.type' => ['required', 'in:in,out'],
        ]);

        /** @var AttendanceDevice $device */
        $device = $request->attributes->get('device');
        $employees = Employee::query()->whereIn('employee_no', array_unique(array_column($data['punches'], 'employee_no')))->pluck('id', 'employee_no');
        $result = ['accepted' => 0, 'duplicates' => 0, 'rejected' => []];

        foreach ($data['punches'] as $index => $punch) {
            $employeeId = $employees[$punch['employee_no']] ?? null;
            $at = Carbon::parse($punch['timestamp'])->setTimezone(config('app.timezone'));

            if ($employeeId === null || $at->isAfter(now()->addMinutes(5))) {
                $result['rejected'][] = ['index' => $index, 'reason' => $employeeId === null ? 'unknown employee_no' : 'timestamp in the future'];

                continue;
            }

            $log = TimeLog::query()->firstOrCreate(
                ['employee_id' => $employeeId, 'logged_at' => $at, 'type' => TimeLogType::from($punch['type'])],
                ['source' => TimeLogSource::Device, 'attendance_device_id' => $device->id, 'ip_address' => $request->ip()],
            );

            $log->wasRecentlyCreated ? $result['accepted']++ : $result['duplicates']++;
        }

        return response()->json($result, $result['accepted'] > 0 ? 201 : 200);
    }
}
