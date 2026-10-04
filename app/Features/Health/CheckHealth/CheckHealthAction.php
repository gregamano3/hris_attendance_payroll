<?php

namespace App\Features\Health\CheckHealth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckHealthAction
{
    /**
     * @return array{database: bool, cache: bool}
     */
    public function handle(): array
    {
        return [
            'database' => $this->check(fn () => DB::select('select 1')),
            'cache' => $this->check(function () {
                Cache::put('health:ping', 'pong', 10);

                return Cache::get('health:ping') === 'pong';
            }),
        ];
    }

    private function check(callable $probe): bool
    {
        try {
            return $probe() !== false;
        } catch (Throwable) {
            return false;
        }
    }
}
