<?php

namespace App\Features\Attendance\Queries;

use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Holiday lookups, cached per year (flushed when holidays change).
 */
class HolidayCalendar
{
    /**
     * Nationwide holidays, plus the branch's local holidays when a branch is given.
     *
     * @return array<string, array{name: string, type: string}> keyed by Y-m-d
     */
    public function forYear(int $year, ?int $branchId = null): array
    {
        $calendar = Cache::rememberForever(self::key($year), function () use ($year) {
            $calendar = ['national' => [], 'branches' => []];

            foreach (Holiday::query()->whereYear('date', $year)->orderBy('date')->get() as $holiday) {
                $entry = ['name' => $holiday->name, 'type' => $holiday->type->value];
                $holiday->branch_id === null
                    ? $calendar['national'][$holiday->date->toDateString()] = $entry
                    : $calendar['branches'][$holiday->branch_id][$holiday->date->toDateString()] = $entry;
            }

            return $calendar;
        });

        $holidays = [...$calendar['national'], ...($branchId !== null ? ($calendar['branches'][$branchId] ?? []) : [])];
        ksort($holidays);

        return $holidays;
    }

    public function typeOn(Carbon $date, ?int $branchId = null): ?HolidayType
    {
        $holiday = $this->forYear($date->year, $branchId)[$date->toDateString()] ?? null;

        return $holiday ? HolidayType::from($holiday['type']) : null;
    }

    public static function flush(int $year): void
    {
        Cache::forget(self::key($year));
    }

    private static function key(int $year): string
    {
        return "attendance:holidays:{$year}";
    }
}
