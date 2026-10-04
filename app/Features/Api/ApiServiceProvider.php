<?php

namespace App\Features\Api;

use App\Shared\Providers\FeatureServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ApiServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        // Per token owner, so several tokens of one user share the budget.
        RateLimiter::for('api-v1', fn (Request $request) => Limit::perMinute((int) config('hris.api.rate_limit_per_minute', 60))
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $schedule
            ->command('sanctum:prune-expired', ['--hours' => 24])->daily()->onOneServer());
    }
}
