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
     * @return array<string, array{name: string, type: string}> keyed by Y-m-d
     */
    public function forYear(int $year): array
    {
        return Cache::rememberForever(self::key($year), fn () => Holiday::query()
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn (Holiday $holiday) => [
                $holiday->date->toDateString() => ['name' => $holiday->name, 'type' => $holiday->type->value],
            ])
            ->all());
    }

    public function typeOn(Carbon $date): ?HolidayType
    {
        $holiday = $this->forYear($date->year)[$date->toDateString()] ?? null;

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
