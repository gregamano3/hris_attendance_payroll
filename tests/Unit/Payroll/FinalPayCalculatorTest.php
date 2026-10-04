<?php

use App\Features\Payroll\Compute\FinalPayCalculator;
use App\Features\Payroll\Compute\FinalPayInput;
use App\Features\Payroll\Compute\PayslipLine;
use App\Features\Payroll\Compute\ThirteenthMonthCalculator;
use App\Features\Payroll\Compute\WithholdingTaxCalculator;
use App\Shared\Money\Money;
use Database\Seeders\PayrollSeeder;

function finalPayCalculator(): FinalPayCalculator
{
    $brackets = array_map(fn (array $b) => [
        'lower' => Money::ofPesos($b[0]), 'upper' => $b[1] === null ? null : Money::ofPesos($b[1]),
        'base' => Money::ofPesos($b[2]), 'rate' => $b[3],
    ], PayrollSeeder::TAX_ANNUAL_2023);

    return new FinalPayCalculator(new ThirteenthMonthCalculator(Money::ofPesos(90000)), new WithholdingTaxCalculator($brackets));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function finalPayInput(array $overrides = []): FinalPayInput
{
    return new FinalPayInput(...[
        'dailyRate' => Money::ofPesos('1379.31'),
        'basicEarningsThisYear' => array_fill(0, 12, Money::ofPesos(15000)), // Jan–Jun at ₱30,000/month
        'thirteenthMonthPaid' => Money::zero(),
        'exemptionUsed' => Money::zero(),
        'unusedLeaveDays' => 0.0,
        'loanBalances' => [],
        'adjustments' => [],
        'taxableToDate' => Money::ofPesos(165300),
        'taxWithheldToDate' => Money::ofPesos(6044.40),
        ...$overrides,
    ]);
}

it('pays the pro-rated 13th month and refunds over-withheld tax (hand-computed)', function () {
    // Separated end of June: basic ₱180,000 → 13th month ₱15,000 (exempt).
    // Annual taxable ₱165,300 is below ₱250,000, so all ₱6,044.40 withheld is refunded.
    $result = finalPayCalculator()->compute(finalPayInput(['unusedLeaveDays' => 7.5]));

    expect($result->amountOf('THIRTEENTH_MONTH')->toDecimal())->toBe('15000.00')
        ->and($result->amountOf('LEAVE_CONVERSION')->toDecimal())->toBe('10344.83') // 7.5 × 1,379.31
        ->and($result->amountOf('TAX_REFUND')->toDecimal())->toBe('6044.40')
        ->and($result->totalDeductions->toDecimal())->toBe('0.00')
        ->and($result->netPay->toDecimal())->toBe('31389.23');
});

it('deducts the 13th month already paid and outstanding loans', function () {
    $result = finalPayCalculator()->compute(finalPayInput([
        'thirteenthMonthPaid' => Money::ofPesos(5000),
        'taxWithheldToDate' => Money::zero(),
        'loanBalances' => ['SSS salary loan' => Money::ofPesos(4000)],
        'adjustments' => [['kind' => PayslipLine::DEDUCTION, 'label' => 'Unreturned laptop', 'amount' => Money::ofPesos(1500), 'taxable' => false]],
    ]));

    expect($result->amountOf('THIRTEENTH_MONTH')->toDecimal())->toBe('10000.00')
        ->and($result->amountOf('LOAN_BALANCE')->toDecimal())->toBe('4000.00')
        ->and($result->amountOf('OTHER_DEDUCTION')->toDecimal())->toBe('1500.00')
        ->and($result->netPay->toDecimal())->toBe('4500.00');
});

it('taxes leave conversion beyond 10 days and annualizes the tax due', function () {
    // ₱1,000/day, 15 unused days: ₱10,000 exempt + ₱5,000 taxable.
    // Year-to-date taxable ₱300,000, withheld ₱6,000. Annual taxable ₱305,000 + ₱5,000 adjustment = ₱310,000
    // → tax 15% × 60,000 = ₱9,000, so ₱3,000 more is withheld.
    $result = finalPayCalculator()->compute(finalPayInput([
        'dailyRate' => Money::ofPesos(1000),
        'basicEarningsThisYear' => [],
        'unusedLeaveDays' => 15.0,
        'taxableToDate' => Money::ofPesos(305000),
        'taxWithheldToDate' => Money::ofPesos(6000),
        'adjustments' => [['kind' => PayslipLine::EARNING, 'label' => 'Salary differential', 'amount' => Money::ofPesos(0), 'taxable' => true]],
    ]));

    expect($result->amountOf('LEAVE_CONVERSION')->toDecimal())->toBe('10000.00')
        ->and($result->amountOf('LEAVE_CONVERSION_TAXABLE')->toDecimal())->toBe('5000.00')
        ->and($result->details['annual_taxable'])->toBe('310000.00')
        ->and($result->amountOf('TAX')->toDecimal())->toBe('3000.00')
        ->and($result->netPay->toDecimal())->toBe('12000.00');
});
