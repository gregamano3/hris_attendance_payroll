<?php

namespace App\Features\Attendance\Compute;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AccrueLeaveCreditsCommand extends Command
{
    protected $signature = 'leaves:accrue {--through= : Last month to accrue (Y-m), defaults to the previous month}';

    protected $description = 'Credit monthly leave accruals (idempotent).';

    public function handle(AccrueLeaveCredits $accrue): int
    {
        $through = $this->option('through')
            ? Carbon::createFromFormat('Y-m', (string) $this->option('through'))->endOfMonth()
            : Carbon::now()->subMonthNoOverflow()->endOfMonth();

        $count = $accrue->handle($through);
        $this->info("Accrued {$count} leave credit(s) through {$through->format('F Y')}.");

        return self::SUCCESS;
    }
}
