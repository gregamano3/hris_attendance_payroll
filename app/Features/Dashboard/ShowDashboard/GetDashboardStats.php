<?php

namespace App\Features\Dashboard\ShowDashboard;

use App\Features\Employees\Queries\EmployeeDirectory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Aggregated numbers for the dashboard widgets. Cached briefly because they
 * are read on every dashboard visit but don't need to be real-time.
 */
class GetDashboardStats
{
    public const CACHE_KEY = 'dashboard:stats';

    public const TTL_SECONDS = 300;

    public function __construct(private EmployeeDirectory $employees) {}

    /**
     * @return array{active_users: int, active_employees: int}
     */
    public function handle(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, fn () => [
            'active_users' => User::query()->where('is_active', true)->count(),
            'active_employees' => $this->employees->activeCount(),
        ]);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
