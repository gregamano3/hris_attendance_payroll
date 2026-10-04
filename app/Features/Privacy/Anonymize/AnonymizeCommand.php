<?php

namespace App\Features\Privacy\Anonymize;

use App\Features\Recruitment\Enums\ApplicantStage;
use App\Features\Recruitment\Models\Applicant;
use Illuminate\Console\Command;

class AnonymizeCommand extends Command
{
    protected $signature = 'privacy:anonymize {--years= : Retention period after separation (default: hris.privacy.retention_years)} {--dry-run : List only}';

    protected $description = 'Anonymize employees whose retention period after separation has ended.';

    public function handle(AnonymizeEmployees $anonymizer): int
    {
        $this->purgeRejectedApplicants();

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

    /**
     * Rejected applicants (and their résumés) are deleted after the applicant retention period.
     */
    private function purgeRejectedApplicants(): void
    {
        $months = (int) config('hris.privacy.applicant_retention_months');
        $query = Applicant::query()->where('stage', ApplicantStage::Rejected)->where('stage_changed_at', '<', now()->subMonths($months));
        $count = $query->count();

        if (! $this->option('dry-run')) {
            $query->get()->each->delete();
        }

        $this->info(($this->option('dry-run') ? 'Would delete' : 'Deleted')." {$count} rejected applicant(s) older than {$months} month(s).");
    }
}
