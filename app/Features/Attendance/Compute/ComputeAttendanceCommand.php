<?php

namespace App\Features\Attendance\Compute;

use App\Features\Employees\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ComputeAttendanceCommand extends Command
{
    protected $signature = 'attendance:compute
        {--from= : First date (Y-m-d), defaults to yesterday}
        {--to= : Last date (Y-m-d), defaults to today}
        {--employee= : Only this employee id}';

    protected $description = 'Compute attendance days for active employees (default: yesterday and today).';

    public function handle(ComputeAttendanceDay $compute): int
    {
        $from = Carbon::parse($this->option('from') ?? 'yesterday');
        $to = Carbon::parse($this->option('to') ?? 'today');

        $employees = Employee::query()
            ->active()
            ->when($this->option('employee'), fn ($q, $id) => $q->whereKey($id))
            ->pluck('id');

        $this->withProgressBar($employees, fn (int $id) => $compute->handleRange($id, $from, $to));
        $this->newLine();
        $this->info("Computed {$employees->count()} employee(s) from {$from->toDateString()} to {$to->toDateString()}.");

        return self::SUCCESS;
    }
}
