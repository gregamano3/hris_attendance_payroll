<?php

namespace App\Shared;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Inclusive date range. Defaults follow the common Philippine semi-monthly
 * cutoffs (1st–15th and 16th–end of month).
 */
final readonly class Period
{
    public Carbon $from;

    public Carbon $to;

    public function __construct(Carbon $from, Carbon $to)
    {
        if ($to->lt($from)) {
            throw new InvalidArgumentException('The period end must not be before its start.');
        }

        $this->from = $from->copy()->startOfDay();
        $this->to = $to->copy()->startOfDay();
    }

    public static function semiMonthlyContaining(Carbon $date): self
    {
        return $date->day <= 15
            ? new self($date->copy()->startOfMonth(), $date->copy()->setDay(15))
            : new self($date->copy()->setDay(16), $date->copy()->endOfMonth());
    }

    /**
     * Read ?from=&to= from the request, defaulting to the current cutoff.
     */
    public static function fromRequest(Request $request, ?Carbon $default = null): self
    {
        $fallback = self::semiMonthlyContaining($default ?? Carbon::today());

        try {
            $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString()) : $fallback->from;
            $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString()) : $fallback->to;

            $period = new self($from, $to);
        } catch (\Throwable) {
            return $fallback;
        }

        // Keep reports bounded.
        return $period->days() > 62 ? new self($period->from, $period->from->copy()->addDays(61)) : $period;
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to) + 1;
    }

    /**
     * @return iterable<Carbon>
     */
    public function dates(): iterable
    {
        for ($date = $this->from->copy(); $date->lte($this->to); $date->addDay()) {
            yield $date->copy();
        }
    }

    public function contains(Carbon $date): bool
    {
        return $date->betweenIncluded($this->from, $this->to->copy()->endOfDay());
    }

    public function label(): string
    {
        return $this->from->isSameMonth($this->to)
            ? $this->from->format('M j').'–'.$this->to->format('j, Y')
            : $this->from->format('M j, Y').' – '.$this->to->format('M j, Y');
    }
}
