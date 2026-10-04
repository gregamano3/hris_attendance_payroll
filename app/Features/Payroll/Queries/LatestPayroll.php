<?php

namespace App\Features\Payroll\Queries;

use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollRun;

class LatestPayroll
{
    public function finalized(): ?PayrollRun
    {
        return PayrollRun::query()->where('status', PayrollRunStatus::Finalized)->latest('period_end')->first();
    }
}
