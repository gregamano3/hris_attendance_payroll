<?php

namespace Database\Seeders;

use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Models\Holiday;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Models\Shift;
use Illuminate\Database\Seeder;

/**
 * Reference data required in every environment: shifts, leave types and the
 * Philippine holiday calendar.
 */
class AttendanceSeeder extends Seeder
{
    /**
     * Philippine holidays for 2026, based on the annual presidential
     * proclamation. Eid'l Fitr and Eid'l Adha are proclaimed separately once
     * their dates are known, so add them from the Holidays page when announced.
     * Always double-check against the official proclamation and adjust the
     * calendar from the Holidays page (moved or added special days).
     *
     * @var list<array{string, string, HolidayType}>
     */
    private const HOLIDAYS_2026 = [
        ['2026-01-01', "New Year's Day", HolidayType::Regular],
        ['2026-02-17', 'Chinese New Year', HolidayType::SpecialNonWorking],
        ['2026-04-02', 'Maundy Thursday', HolidayType::Regular],
        ['2026-04-03', 'Good Friday', HolidayType::Regular],
        ['2026-04-04', 'Black Saturday', HolidayType::SpecialNonWorking],
        ['2026-04-09', 'Araw ng Kagitingan', HolidayType::Regular],
        ['2026-05-01', 'Labor Day', HolidayType::Regular],
        ['2026-06-12', 'Independence Day', HolidayType::Regular],
        ['2026-08-21', 'Ninoy Aquino Day', HolidayType::SpecialNonWorking],
        ['2026-08-31', 'National Heroes Day', HolidayType::Regular],
        ['2026-11-01', "All Saints' Day", HolidayType::SpecialNonWorking],
        ['2026-11-02', "All Souls' Day", HolidayType::SpecialNonWorking],
        ['2026-11-30', 'Bonifacio Day', HolidayType::Regular],
        ['2026-12-08', 'Feast of the Immaculate Conception of Mary', HolidayType::SpecialNonWorking],
        ['2026-12-24', 'Christmas Eve', HolidayType::SpecialNonWorking],
        ['2026-12-25', 'Christmas Day', HolidayType::Regular],
        ['2026-12-30', 'Rizal Day', HolidayType::Regular],
        ['2026-12-31', 'Last Day of the Year', HolidayType::SpecialNonWorking],
    ];

    /**
     * Statutory and common company leaves.
     *
     * @var list<array{string, string, bool, int}>
     */
    private const LEAVE_TYPES = [
        ['VL', 'Vacation Leave', true, 15],
        ['SL', 'Sick Leave', true, 15],
        ['SIL', 'Service Incentive Leave', true, 5],
        ['ML', 'Maternity Leave', true, 105],
        ['PL', 'Paternity Leave', true, 7],
        ['SPL', 'Solo Parent Leave', true, 7],
        ['VAWC', 'VAWC Leave', true, 10],
        ['LWOP', 'Leave Without Pay', false, 0],
    ];

    public function run(): void
    {
        Shift::query()->firstOrCreate(['name' => 'Regular Day Shift'], [
            'start_time' => '08:00:00', 'end_time' => '17:00:00', 'break_minutes' => 60,
            'grace_minutes' => 5, 'work_days' => [1, 2, 3, 4, 5], 'is_default' => ! Shift::query()->where('is_default', true)->exists(),
        ]);

        Shift::query()->firstOrCreate(['name' => 'Night Shift'], [
            'start_time' => '22:00:00', 'end_time' => '07:00:00', 'break_minutes' => 60,
            'grace_minutes' => 5, 'work_days' => [1, 2, 3, 4, 5], 'is_default' => false,
        ]);

        Shift::query()->firstOrCreate(['name' => 'Six-day Shift'], [
            'start_time' => '08:00:00', 'end_time' => '16:00:00', 'break_minutes' => 30,
            'grace_minutes' => 5, 'work_days' => [1, 2, 3, 4, 5, 6], 'is_default' => false,
        ]);

        foreach (self::LEAVE_TYPES as [$code, $name, $paid, $days]) {
            LeaveType::query()->firstOrCreate(['code' => $code], [
                'name' => $name, 'is_paid' => $paid, 'days_per_year' => $days,
                'is_convertible' => in_array($code, ['VL', 'SIL'], true), // unused credits paid out on separation
            ]);
        }

        foreach (self::HOLIDAYS_2026 as [$date, $name, $type]) {
            Holiday::query()->firstOrCreate(['date' => $date], ['name' => $name, 'type' => $type]);
        }
    }
}
