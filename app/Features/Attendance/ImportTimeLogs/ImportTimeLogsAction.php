<?php

namespace App\Features\Attendance\ImportTimeLogs;

use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SplFileObject;
use Throwable;

/**
 * Imports punches exported by biometric devices.
 *
 * Expected CSV header: employee_no,logged_at,type
 *   EMP-00001,2026-10-05 07:58,in
 * Exact duplicates (same employee, time and type) are skipped.
 */
class ImportTimeLogsAction
{
    private const MAX_ERRORS = 50;

    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function handle(string $path, ?int $userId): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

        $employees = Employee::query()->pluck('id', 'employee_no');
        $header = null;
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($file, $employees, $userId, &$header, &$result) {
            foreach ($file as $index => $row) {
                if (! is_array($row) || $row === [null]) {
                    continue;
                }

                $row = array_map(fn ($value) => trim((string) $value), $row);

                if ($header === null) {
                    $header = array_map('strtolower', $row);

                    if (array_diff(['employee_no', 'logged_at', 'type'], $header) !== []) {
                        $result['errors'][] = 'The header must contain employee_no, logged_at and type.';

                        return;
                    }

                    continue;
                }

                $line = $index + 1;
                $data = array_combine($header, array_pad($row, count($header), ''));

                $employeeId = $employees[$data['employee_no']] ?? null;
                $type = TimeLogType::tryFrom(strtolower($data['type']));

                try {
                    $loggedAt = Carbon::parse($data['logged_at']);
                } catch (Throwable) {
                    $loggedAt = null;
                }

                $error = match (true) {
                    $employeeId === null => "Line {$line}: unknown employee [{$data['employee_no']}].",
                    $type === null => "Line {$line}: type must be \"in\" or \"out\".",
                    $loggedAt === null => "Line {$line}: invalid date/time [{$data['logged_at']}].",
                    $loggedAt->isFuture() => "Line {$line}: date/time is in the future.",
                    default => null,
                };

                if ($error !== null) {
                    if (count($result['errors']) < self::MAX_ERRORS) {
                        $result['errors'][] = $error;
                    }

                    continue;
                }

                $log = TimeLog::query()->firstOrCreate(
                    ['employee_id' => $employeeId, 'logged_at' => $loggedAt, 'type' => $type],
                    ['source' => TimeLogSource::Import, 'created_by' => $userId],
                );

                $log->wasRecentlyCreated ? $result['imported']++ : $result['skipped']++;
            }
        });

        return $result;
    }
}
