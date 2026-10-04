<?php

namespace App\Features\Employees\Queries;

use App\Features\Employees\Models\Branch;
use App\Features\Employees\Models\CostCenter;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Position;
use Illuminate\Support\Facades\Cache;

/**
 * Cached select options for departments and positions. Reference data like
 * this is read on almost every form but rarely changes, so it is cached
 * forever and flushed by model events (see EmployeesServiceProvider).
 */
class EmployeeOptions
{
    public const DEPARTMENTS_KEY = 'employees:options:departments';

    public const POSITIONS_KEY = 'employees:options:positions';

    public const BRANCHES_KEY = 'employees:options:branches';

    public const COST_CENTERS_KEY = 'employees:options:cost-centers';

    /**
     * @return array<int, string>
     */
    public function departments(): array
    {
        return Cache::rememberForever(self::DEPARTMENTS_KEY, fn () => Department::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all());
    }

    /**
     * Positions grouped by department name.
     *
     * @return array<string, array<int, string>>
     */
    public function positions(): array
    {
        return Cache::rememberForever(self::POSITIONS_KEY, fn () => Position::query()
            ->with('department:id,name')
            ->orderBy('title')
            ->get()
            ->groupBy(fn (Position $position) => $position->department->name ?? 'Unassigned')
            ->sortKeys()
            ->map(fn ($positions) => $positions->pluck('title', 'id')->all())
            ->all());
    }

    /**
     * @return array<int, string>
     */
    public function branches(): array
    {
        return Cache::rememberForever(self::BRANCHES_KEY, fn () => Branch::query()->orderBy('name')->pluck('name', 'id')->all());
    }

    /**
     * @return array<int, string>
     */
    public function costCenters(): array
    {
        return Cache::rememberForever(self::COST_CENTERS_KEY, fn () => CostCenter::query()->orderBy('code')->get()
            ->mapWithKeys(fn (CostCenter $c): array => [$c->id => "{$c->code} — {$c->name}"])->all());
    }

    public static function flush(): void
    {
        Cache::forget(self::BRANCHES_KEY);
        Cache::forget(self::COST_CENTERS_KEY);
        Cache::forget(self::DEPARTMENTS_KEY);
        Cache::forget(self::POSITIONS_KEY);
    }
}
