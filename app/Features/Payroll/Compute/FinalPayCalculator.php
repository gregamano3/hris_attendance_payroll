<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

/**
 * Final pay on separation (DOLE Labor Advisory 06-2020):
 *  - pro-rated 13th month pay not yet paid this year
 *  - cash conversion of unused convertible leave credits (up to 10 days of
 *    monetized leave is a non-taxable de minimis benefit)
 *  - manual earnings / deductions (last salary differentials, clearance items)
 *  - outstanding loan balances
 *  - year-end tax annualization: annual tax on year-to-date plus final pay
 *    taxable income, less tax already withheld (refunded when negative)
 *
 * The last regular salary is paid through the regular payroll run covering
 * the separation date.
 */
class FinalPayCalculator
{
    public function __construct(
        private ThirteenthMonthCalculator $thirteenthMonth,
        private WithholdingTaxCalculator $annualTax,
        private float $exemptLeaveDays = 10,
    ) {}

    public function compute(FinalPayInput $input): FinalPayResult
    {
        $lines = [];

        // 13th month: what is due for the year minus what was already paid.
        $due = $this->thirteenthMonth->compute($input->basicEarningsThisYear, $input->exemptionUsed);
        $thirteenth = $due['amount']->minus($input->thirteenthMonthPaid)->max(Money::zero());
        $thirteenthExempt = $thirteenth->min($due['exempt']);

        if (! $thirteenth->isZero()) {
            $lines[] = new PayslipLine(PayslipLine::EARNING, 'THIRTEENTH_MONTH', 'Pro-rated 13th month pay', $thirteenthExempt);

            if ($thirteenth->isGreaterThan($thirteenthExempt)) {
                $lines[] = new PayslipLine(PayslipLine::EARNING, 'THIRTEENTH_MONTH_TAXABLE', 'Pro-rated 13th month pay (taxable excess)', $thirteenth->minus($thirteenthExempt), taxable: true);
            }
        }

        // Unused leave credits.
        if ($input->unusedLeaveDays > 0) {
            $exemptDays = min($input->unusedLeaveDays, $this->exemptLeaveDays);
            $taxableDays = $input->unusedLeaveDays - $exemptDays;

            $lines[] = new PayslipLine(PayslipLine::EARNING, 'LEAVE_CONVERSION', 'Unused leave credits', $input->dailyRate->multipliedBy($exemptDays), $exemptDays, 'days');

            if ($taxableDays > 0) {
                $lines[] = new PayslipLine(PayslipLine::EARNING, 'LEAVE_CONVERSION_TAXABLE', 'Unused leave credits (taxable)', $input->dailyRate->multipliedBy($taxableDays), $taxableDays, 'days', taxable: true);
            }
        }

        foreach ($input->adjustments as $adjustment) {
            $lines[] = $adjustment['kind'] === PayslipLine::EARNING
                ? new PayslipLine(PayslipLine::EARNING, 'ADJUSTMENT', $adjustment['label'], $adjustment['amount'], taxable: $adjustment['taxable'])
                : new PayslipLine(PayslipLine::DEDUCTION, 'OTHER_DEDUCTION', $adjustment['label'], $adjustment['amount']);
        }

        foreach ($input->loanBalances as $label => $balance) {
            $lines[] = new PayslipLine(PayslipLine::DEDUCTION, 'LOAN_BALANCE', "Outstanding {$label}", $balance);
        }

        // Annualize: tax on the whole year's taxable income vs tax already withheld.
        $finalTaxable = array_reduce($lines, fn (Money $c, PayslipLine $l) => $l->kind === PayslipLine::EARNING && $l->taxable ? $c->plus($l->amount) : $c, Money::zero());
        $annualTaxable = $input->taxableToDate->plus($finalTaxable);
        $taxDue = $this->annualTax->compute($annualTaxable);
        $taxAdjustment = $taxDue->minus($input->taxWithheldToDate);

        if ($taxAdjustment->isNegative()) {
            $lines[] = new PayslipLine(PayslipLine::EARNING, 'TAX_REFUND', 'Refund of excess tax withheld', $taxAdjustment->multipliedBy(-1));
        } elseif (! $taxAdjustment->isZero()) {
            $lines[] = new PayslipLine(PayslipLine::DEDUCTION, 'TAX', 'Withholding tax (annualized)', $taxAdjustment);
        }

        $earnings = $this->sum($lines, PayslipLine::EARNING);
        $deductions = $this->sum($lines, PayslipLine::DEDUCTION);

        return new FinalPayResult(
            lines: $lines,
            totalEarnings: $earnings,
            totalDeductions: $deductions,
            netPay: $earnings->minus($deductions),
            details: [
                'daily_rate' => $input->dailyRate->toDecimal(),
                'basic_salary_this_year' => $due['basic']->toDecimal(),
                'thirteenth_month_due' => $due['amount']->toDecimal(),
                'thirteenth_month_paid' => $input->thirteenthMonthPaid->toDecimal(),
                'unused_leave_days' => $input->unusedLeaveDays,
                'taxable_to_date' => $input->taxableToDate->toDecimal(),
                'annual_taxable' => $annualTaxable->toDecimal(),
                'annual_tax_due' => $taxDue->toDecimal(),
                'tax_withheld_to_date' => $input->taxWithheldToDate->toDecimal(),
            ],
        );
    }

    /**
     * @param  list<PayslipLine>  $lines
     */
    private function sum(array $lines, string $kind): Money
    {
        return array_reduce($lines, fn (Money $c, PayslipLine $l) => $l->kind === $kind ? $c->plus($l->amount) : $c, Money::zero());
    }
}
