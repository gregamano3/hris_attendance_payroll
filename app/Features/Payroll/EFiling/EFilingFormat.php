<?php

namespace App\Features\Payroll\EFiling;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface EFilingFormat
{
    public function key(): string;

    public function label(): string;

    /** monthly | annual */
    public function frequency(): string;

    /**
     * @param  Collection<int, array<string, mixed>>  $rows  MonthlyContributions or AnnualCompensation rows
     */
    public function render(Collection $rows, Carbon $period): string;

    public function filename(Carbon $period): string;
}
