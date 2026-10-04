<?php

namespace App\Features\Attendance\Compute;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Queued recomputation after punches, shifts, holidays or leaves change.
 */
class RecomputeAttendanceJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public function __construct(
        public int $employeeId,
        public string $from,
        public string $to,
    ) {}

    public static function forDates(int $employeeId, Carbon $from, ?Carbon $to = null): self
    {
        return new self($employeeId, $from->toDateString(), ($to ?? $from)->toDateString());
    }

    public function uniqueId(): string
    {
        return "{$this->employeeId}:{$this->from}:{$this->to}";
    }

    public function handle(ComputeAttendanceDay $compute): void
    {
        $compute->handleRange($this->employeeId, Carbon::parse($this->from), Carbon::parse($this->to));
    }
}
