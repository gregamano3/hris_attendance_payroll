<?php

namespace App\Features\Payroll\ListPayrollRuns;

use App\Features\Payroll\Models\PayrollRun;
use Illuminate\View\View;

class ListPayrollRunsController
{
    public function __invoke(): View
    {
        return view('payroll::runs.index', [
            'runs' => PayrollRun::query()->latest('period_start')->paginate(15),
        ]);
    }
}
