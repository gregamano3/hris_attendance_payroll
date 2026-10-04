<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;
use Illuminate\Support\Carbon;

/**
 * Computes one employee's payslip for a semi-monthly run. Pure: all rates,
 * attendance and statutory parameters are passed in.
 *
 * Daily rate: monthly × 12 ÷ days per year (monthly-rated) or the daily rate.
 * Every time-based amount is derived from the daily rate (minutes ÷ minutes
 * per day × multiplier) so rounding never compounds.
 *
 * Monthly-rated: half the monthly salary, less absences (unpaid days) and
 * late/undertime, plus premiums for work outside ordinary days (only the part
 * not already covered by the salary).
 * Daily-rated: worked hours, unworked regular holidays and paid leaves.
 *
 * Minimum wage earners (RR 11-2018): statutory minimum wage, holiday pay,
 * overtime, night differential and premiums are exempt from withholding tax;
 * only other taxable income (e.g. taxable allowances) is taxed.
 */
class PayslipCalculator
{
    /**
     * @param  array<string, float>  $multipliers
     */
    public function __construct(
        private SssCalculator $sss,
        private PhilHealthCalculator $philHealth,
        private PagIbigCalculator $pagIbig,
        private WithholdingTaxCalculator $tax,
        private int $daysPerYear = 261,
        private int $hoursPerDay = 8,
        private float $contributionFraction = 0.5,
        private float $salaryFraction = 0.5,
        private array $multipliers = [],
        private float $overtimeRegular = 1.25,
        private float $overtimePremium = 1.30,
        private float $nightDifferential = 0.10,
        private ?WithholdingTaxCalculator $annualTax = null,
    ) {}

    private const DAY_TYPE_LABELS = [
        'rest_day' => 'Rest day',
        'special' => 'Special day',
        'special_rest' => 'Special day on rest day',
        'regular_holiday' => 'Regular holiday',
        'regular_holiday_rest' => 'Regular holiday on rest day',
    ];

    public function compute(PayslipInput $input): PayslipResult
    {
        $lines = [];
        $warnings = [];
        $stats = [
            'days_worked' => 0, 'days_absent' => 0, 'days_incomplete' => 0, 'paid_leave_days' => 0,
            'unpaid_leave_days' => 0, 'holiday_days' => 0, 'late_minutes' => 0, 'undertime_minutes' => 0,
            'overtime_minutes' => 0, 'night_diff_minutes' => 0,
        ];

        // Earnings are computed per rate segment (a salary change inside the
        // period splits it); contributions and tax are computed once below.
        $segments = $input->rateSegments ?: [['from' => null, 'rate' => $input->basicRate]];
        $periodDays = $input->periodFrom !== null && $input->periodTo !== null
            ? (int) Carbon::parse($input->periodFrom)->diffInDays(Carbon::parse($input->periodTo)) + 1
            : null;

        foreach ($segments as $i => $segment) {
            $next = $segments[$i + 1]['from'] ?? null;
            $segmentDays = array_values(array_filter($input->days, fn (DayData $d) => ($segment['from'] === null || $d->date >= $segment['from'])
                && ($next === null || $d->date < $next)));

            $share = 1.0;

            if (count($segments) > 1 && $periodDays !== null) {
                $from = max($segment['from'] ?? $input->periodFrom, $input->periodFrom);
                $to = $next !== null ? Carbon::parse($next)->subDay()->toDateString() : $input->periodTo;
                $share = ((int) Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1) / $periodDays;
            }

            $lines = $this->mergeLines($lines, $this->earningLines($input, $segmentDays, $segment['rate'], $share, $stats));
        }

        if (count($segments) > 1) {
            $warnings[] = 'Pro-rated for a salary change within the period.';
        }

        $daily = $this->dailyRate($input->monthlyRated, $input->basicRate);

        // Adjustments, recurring allowances and loan amortizations
        foreach ($input->adjustments as $adjustment) {
            $lines[] = $adjustment['kind'] === PayslipLine::EARNING
                ? new PayslipLine(PayslipLine::EARNING, $adjustment['code'] ?? 'ALLOWANCE', $adjustment['label'], $adjustment['amount'], taxable: $adjustment['taxable'])
                : new PayslipLine(PayslipLine::DEDUCTION, $adjustment['code'] ?? 'OTHER_DEDUCTION', $adjustment['label'], $adjustment['amount']);
        }

        if ($input->minimumWageEarner) {
            $lines = array_map(fn (PayslipLine $l) => $l->code === 'ALLOWANCE' || $l->kind !== PayslipLine::EARNING ? $l
                : new PayslipLine($l->kind, $l->code, $l->label, $l->amount, $l->quantity, $l->unit, taxable: false), $lines);
        }

        $gross = $this->sum($lines, PayslipLine::EARNING);
        $taxableEarnings = $this->sum(array_filter($lines, fn (PayslipLine $l) => $l->taxable), PayslipLine::EARNING);

        // Statutory contributions on the monthly equivalent compensation
        $monthly = $input->monthlyRated ? $input->basicRate : $daily->multipliedBy($this->daysPerYear / 12);
        $sss = $this->sss->monthly($monthly);
        $philHealth = $this->philHealth->monthly($monthly);
        $pagIbig = $this->pagIbig->monthly($monthly);
        $share = fn (Money $amount) => $amount->multipliedBy($this->contributionFraction);

        $contributions = [
            new PayslipLine(PayslipLine::DEDUCTION, 'SSS', 'SSS contribution', $share($sss['employee'])),
            new PayslipLine(PayslipLine::DEDUCTION, 'PHILHEALTH', 'PhilHealth contribution', $share($philHealth['employee'])),
            new PayslipLine(PayslipLine::DEDUCTION, 'PAGIBIG', 'Pag-IBIG contribution', $share($pagIbig['employee'])),
        ];
        $employeeContributions = $this->sum($contributions, PayslipLine::DEDUCTION);

        // Contributions reduce taxable pay, except for minimum wage earners whose
        // contributions relate to their exempt wages.
        $taxable = ($input->minimumWageEarner ? $taxableEarnings : $taxableEarnings->minus($employeeContributions))->max(Money::zero());
        $withholding = $this->tax->compute($taxable);

        // Year-end annualization (last payroll of the year): withhold the
        // balance of the annual tax due, or refund what was over-withheld.
        if ($input->annualization !== null && $this->annualTax !== null) {
            $annualTaxable = $input->annualization['taxable_to_date']->plus($taxable);
            $due = $this->annualTax->compute($annualTaxable);
            $balance = $due->minus($input->annualization['withheld_to_date']);
            $withholding = $balance->max(Money::zero());

            if ($balance->isNegative()) {
                $refund = $balance->multipliedBy(-1);
                $lines[] = new PayslipLine(PayslipLine::EARNING, 'TAX_REFUND', 'Refund of excess tax withheld (annualization)', $refund);
                $gross = $gross->plus($refund);
            }

            $warnings[] = sprintf('Year-end annualization: annual taxable %s, tax due %s, withheld before this run %s.',
                $annualTaxable->format(), $due->format(), $input->annualization['withheld_to_date']->format());
        }

        $lines = [
            ...$lines,
            ...$contributions,
            new PayslipLine(PayslipLine::DEDUCTION, 'TAX', $input->annualization !== null && $this->annualTax !== null ? 'Withholding tax (annualized)' : 'Withholding tax', $withholding),
            new PayslipLine(PayslipLine::EMPLOYER, 'SSS_ER', 'SSS (employer)', $share($sss['employer'])),
            new PayslipLine(PayslipLine::EMPLOYER, 'SSS_EC', 'SSS EC (employer)', $share($sss['ec'])),
            new PayslipLine(PayslipLine::EMPLOYER, 'PHILHEALTH_ER', 'PhilHealth (employer)', $share($philHealth['employer'])),
            new PayslipLine(PayslipLine::EMPLOYER, 'PAGIBIG_ER', 'Pag-IBIG (employer)', $share($pagIbig['employer'])),
        ];

        $deductions = $this->sum($lines, PayslipLine::DEDUCTION);
        $net = $gross->minus($deductions);

        if (($stats['holidays_unpaid'] ?? 0) > 0) {
            $warnings[] = "{$stats['holidays_unpaid']} regular holiday(s) unpaid: absent on the preceding work day.";
        }

        if ($stats['days_incomplete'] > 0) {
            $warnings[] = "{$stats['days_incomplete']} day(s) with incomplete punches were treated as unpaid.";
        }

        if ($net->isNegative()) {
            $warnings[] = 'Net pay is negative.';
        }

        return new PayslipResult(
            dailyRate: $daily,
            hourlyRate: $daily->dividedBy($this->hoursPerDay),
            lines: array_values(array_filter($lines, fn (PayslipLine $l) => ! $l->amount->isZero() || in_array($l->code, ['BASIC', 'SSS', 'PHILHEALTH', 'PAGIBIG', 'TAX'], true))),
            grossPay: $gross,
            taxableIncome: $taxable,
            totalDeductions: $deductions,
            netPay: $net,
            employerContributions: $this->sum($lines, PayslipLine::EMPLOYER),
            attendance: $stats,
            warnings: $warnings,
        );
    }

    private function dailyRate(bool $monthlyRated, Money $basicRate): Money
    {
        return $monthlyRated ? $basicRate->multipliedBy(12)->dividedBy($this->daysPerYear) : $basicRate;
    }

    /**
     * Attendance-based earnings for the days of one rate segment.
     *
     * @param  list<DayData>  $days
     * @param  array<string, int|float>  $stats
     * @return list<PayslipLine>
     */
    private function earningLines(PayslipInput $input, array $days, Money $basicRate, float $salaryShare, array &$stats): array
    {
        $daily = $this->dailyRate($input->monthlyRated, $basicRate);
        $minutesPerDay = $this->hoursPerDay * 60;
        $forMinutes = fn (int $minutes, float $factor = 1.0): Money => $daily->multipliedBy($minutes / $minutesPerDay * $factor);

        $lines = [];

        $premiumMinutes = [];    // day type => worked minutes
        $overtimeMinutes = [];   // day type => OT minutes
        $nightMinutes = [];      // day type => ND minutes
        $regularWorked = 0;
        $tardiness = 0;
        $unpaidDays = 0.0;
        $paidLeaveDays = 0.0;
        $holidayPayDays = 0;

        foreach ($days as $day) {
            $type = $day->dayType();

            switch ($day->status) {
                case DayData::PRESENT:
                    $stats['days_worked']++;
                    $stats['late_minutes'] += $day->lateMinutes;
                    $stats['undertime_minutes'] += $day->undertimeMinutes;
                    $stats['overtime_minutes'] += $day->overtimeMinutes;
                    $stats['night_diff_minutes'] += $day->nightDiffMinutes;

                    if ($type === 'regular') {
                        $regularWorked += $day->workedMinutes;
                        $tardiness += $day->lateMinutes + $day->undertimeMinutes;
                    } else {
                        $premiumMinutes[$type] = ($premiumMinutes[$type] ?? 0) + $day->workedMinutes;
                    }

                    $overtimeMinutes[$type] = ($overtimeMinutes[$type] ?? 0) + $day->overtimeMinutes;
                    $nightMinutes[$type] = ($nightMinutes[$type] ?? 0) + $day->nightDiffMinutes;

                    // Half-day leave: the leave half is paid leave or unpaid absence.
                    if ($day->leaveFraction > 0) {
                        $day->paidLeave ? $paidLeaveDays += $day->leaveFraction : $unpaidDays += $day->leaveFraction;
                        $stats[$day->paidLeave ? 'paid_leave_days' : 'unpaid_leave_days'] += $day->leaveFraction;
                    }
                    break;

                case DayData::LEAVE:
                    if ($day->paidLeave) {
                        $stats['paid_leave_days']++;
                        $paidLeaveDays++;
                    } else {
                        $stats['unpaid_leave_days']++;
                        $unpaidDays++;
                    }
                    break;

                case DayData::HOLIDAY:
                    $stats['holiday_days']++;

                    if ($day->holiday === 'regular' && ! $day->isRestDay) {
                        if ($day->holidayPayEligible) {
                            $holidayPayDays++;
                        } else {
                            // Not entitled: unpaid (monthly salaries are reduced by a day).
                            $stats['holidays_unpaid'] = ($stats['holidays_unpaid'] ?? 0) + 1;
                            $unpaidDays++;
                        }
                    }
                    break;

                case DayData::ABSENT:
                    $stats['days_absent']++;

                    // Absent on the working half of a paid half-day leave.
                    if ($day->leaveFraction > 0 && $day->paidLeave) {
                        $paidLeaveDays += $day->leaveFraction;
                        $stats['paid_leave_days'] += $day->leaveFraction;
                        $unpaidDays += 1 - $day->leaveFraction;
                    } else {
                        $unpaidDays++;
                    }
                    break;

                case DayData::INCOMPLETE:
                    $stats['days_incomplete']++;
                    $unpaidDays++;
                    break;
            }
        }

        // Basic pay
        if ($input->monthlyRated) {
            $lines[] = $this->earning('BASIC', 'Basic pay', $basicRate->multipliedBy($this->salaryFraction * $salaryShare));

            if ($unpaidDays > 0) {
                $lines[] = $this->earning('ABSENCES', 'Less: absences / unpaid leave', $daily->multipliedBy(-$unpaidDays), $unpaidDays, 'days');
            }

            if ($tardiness > 0) {
                $lines[] = $this->earning('TARDINESS', 'Less: late / undertime', $forMinutes($tardiness)->multipliedBy(-1), round($tardiness / 60, 2), 'hrs');
            }
        } else {
            $lines[] = $this->earning('BASIC', 'Basic pay', $forMinutes($regularWorked), round($regularWorked / 60, 2), 'hrs');

            if ($holidayPayDays > 0) {
                $lines[] = $this->earning('HOLIDAY_PAY', 'Regular holiday pay (unworked)', $daily->multipliedBy($holidayPayDays), $holidayPayDays, 'days');
            }

            if ($paidLeaveDays > 0) {
                $lines[] = $this->earning('PAID_LEAVE', 'Paid leave', $daily->multipliedBy($paidLeaveDays), $paidLeaveDays, 'days');
            }
        }

        // Work on rest days and holidays
        foreach (self::DAY_TYPE_LABELS as $type => $label) {
            $minutes = $premiumMinutes[$type] ?? 0;

            if ($minutes === 0) {
                continue;
            }

            $multiplier = $this->multiplier($type);
            // The salary of monthly-rated employees already covers ordinary work days.
            $onWorkDay = in_array($type, ['special', 'regular_holiday'], true);
            $factor = $input->monthlyRated && $onWorkDay ? $multiplier - 1 : $multiplier;

            $lines[] = $this->earning(
                'PREMIUM_'.strtoupper($type),
                sprintf('%s work (%d%%)', $label, round($multiplier * 100)),
                $forMinutes($minutes, $factor),
                round($minutes / 60, 2),
                'hrs',
            );
        }

        // Overtime
        foreach ($overtimeMinutes as $type => $minutes) {
            if ($minutes === 0) {
                continue;
            }

            $factor = $type === 'regular' ? $this->overtimeRegular : $this->multiplier($type) * $this->overtimePremium;
            $label = $type === 'regular' ? 'Overtime' : 'Overtime – '.strtolower(self::DAY_TYPE_LABELS[$type]);

            $lines[] = $this->earning('OT_'.strtoupper($type), sprintf('%s (%s%%)', $label, rtrim(rtrim(number_format($factor * 100, 1), '0'), '.')), $forMinutes($minutes, $factor), round($minutes / 60, 2), 'hrs');
        }

        // Night differential
        $nightPay = Money::zero();
        $nightTotal = 0;

        foreach ($nightMinutes as $type => $minutes) {
            $nightPay = $nightPay->plus($forMinutes($minutes, $this->multiplier($type) * $this->nightDifferential));
            $nightTotal += $minutes;
        }

        if ($nightTotal > 0) {
            $lines[] = $this->earning('NIGHT_DIFF', 'Night differential (10%)', $nightPay, round($nightTotal / 60, 2), 'hrs');
        }

        return $lines;
    }

    /**
     * @param  list<PayslipLine>  $lines
     * @param  list<PayslipLine>  $more
     * @return list<PayslipLine>
     */
    private function mergeLines(array $lines, array $more): array
    {
        foreach ($more as $line) {
            foreach ($lines as $i => $existing) {
                if ($existing->code === $line->code && $existing->kind === $line->kind) {
                    $lines[$i] = new PayslipLine(
                        $existing->kind, $existing->code, $existing->label, $existing->amount->plus($line->amount),
                        $existing->quantity === null && $line->quantity === null ? null : round(($existing->quantity ?? 0) + ($line->quantity ?? 0), 2),
                        $existing->unit ?? $line->unit, $existing->taxable,
                    );

                    continue 2;
                }
            }

            $lines[] = $line;
        }

        return $lines;
    }

    private function multiplier(string $type): float
    {
        return (float) ($this->multipliers[$type] ?? 1.0);
    }

    private function earning(string $code, string $label, Money $amount, int|float|null $quantity = null, ?string $unit = null): PayslipLine
    {
        return new PayslipLine(PayslipLine::EARNING, $code, $label, $amount, $quantity === null ? null : (float) $quantity, $unit, taxable: true);
    }

    /**
     * @param  iterable<PayslipLine>  $lines
     */
    private function sum(iterable $lines, string $kind): Money
    {
        $total = Money::zero();

        foreach ($lines as $line) {
            if ($line->kind === $kind) {
                $total = $total->plus($line->amount);
            }
        }

        return $total;
    }
}
