<?php

namespace App\Features\Privacy\Anonymize;

use Illuminate\Console\Command;

class AnonymizeCommand extends Command
{
    protected $signature = 'privacy:anonymize {--years= : Retention period after separation (default: hris.privacy.retention_years)} {--dry-run : List only}';

    protected $description = 'Anonymize employees whose retention period after separation has ended.';

    public function handle(AnonymizeEmployees $anonymizer): int
    {
        $years = (int) ($this->option('years') ?: config('hris.privacy.retention_years'));
        $due = $anonymizer->due($years);

        if ($due->isEmpty()) {
            $this->info("No employees separated more than {$years} year(s) ago.");

            return self::SUCCESS;
        }

        $this->table(['ID', 'Employee no.', 'Separated'], $due->map(fn ($e) => [$e->id, $e->employee_no, $e->separated_at?->toDateString()])->all());

        if ($this->option('dry-run')) {
            $this->info("{$due->count()} employee(s) would be anonymized.");

            return self::SUCCESS;
        }

        $due->each(fn ($employee) => $anonymizer->anonymize($employee));
        $this->info("Anonymized {$due->count()} employee(s).");

        return self::SUCCESS;
    }
}
