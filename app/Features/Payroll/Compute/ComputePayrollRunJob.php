<?php

namespace App\Features\Payroll\Compute;

use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/**
 * Computes a payroll run in the background. The run is in the "computing"
 * state until the job succeeds (computed) or fails (back to draft with the
 * error message).
 */
class ComputePayrollRunJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public PayrollRun $run) {}

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("payroll-run-{$this->run->id}"))->dontRelease()];
    }

    public function handle(ComputePayrollRun $regular, ComputeThirteenthMonthRun $thirteenthMonth): void
    {
        $this->run->isThirteenthMonth() ? $thirteenthMonth->handle($this->run) : $regular->handle($this->run);
    }

    public function failed(?Throwable $exception): void
    {
        $this->run->update([
            'status' => PayrollRunStatus::Draft,
            'progress' => 0,
            'compute_error' => $exception?->getMessage() ?? 'The computation failed.',
        ]);
    }
}
