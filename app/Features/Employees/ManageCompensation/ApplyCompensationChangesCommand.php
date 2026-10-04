<?php

namespace App\Features\Employees\ManageCompensation;

use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\CompensationHistory;
use Illuminate\Console\Command;

class ApplyCompensationChangesCommand extends Command
{
    protected $signature = 'compensation:apply';

    protected $description = 'Apply salary changes that become effective today to the employees\' current rate.';

    public function handle(CompensationHistory $history): int
    {
        Employee::query()->each(fn (Employee $employee) => $history->syncCurrent($employee));
        $this->info('Current rates synchronized.');

        return self::SUCCESS;
    }
}
