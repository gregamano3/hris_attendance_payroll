<?php

use App\Features\Payroll\Compute\DayData;
use App\Features\Payroll\Compute\PagIbigCalculator;
use App\Features\Payroll\Compute\PayslipCalculator;
use App\Features\Payroll\Compute\PayslipInput;
use App\Features\Payroll\Compute\PayslipLine;
use App\Features\Payroll\Compute\PhilHealthCalculator;
use App\Features\Payroll\Compute\SssCalculator;
use App\Features\Payroll\Compute\WithholdingTaxCalculator;
use App\Shared\Money\Money;
use Database\Seeders\PayrollSeeder;

function payslipCalculator(): PayslipCalculator
{
    $brackets = array_map(fn (array $b) => [
        'lower' => Money::ofPesos($b[0]),
        'upper' => $b[1] === null ? null : Money::ofPesos($b[1]),
        'base' => Money::ofPesos($b[2]),
        'rate' => $b[3],
    ], PayrollSeeder::TAX_SEMI_MONTHLY_2023);

    return new PayslipCalculator(
        new SssCalculator(PayrollSeeder::SSS_2025),
        new PhilHealthCalculator(PayrollSeeder::PHILHEALTH_2025),
        new PagIbigCalculator(PayrollSeeder::PAGIBIG_2024),
        new WithholdingTaxCalculator($brackets),
        daysPerYear: 261,
        multipliers: [
            'regular' => 1.00, 'rest_day' => 1.30, 'special' => 1.30, 'special_rest' => 1.50,
            'regular_holiday' => 2.00, 'regular_holiday_rest' => 2.60,
        ],
    );
}

function workday(string $date, int $worked = 480, int $late = 0, int $ot = 0, int $nd = 0): DayData
{
    return new DayData($date, DayData::PRESENT, workedMinutes: $worked, lateMinutes: $late, overtimeMinutes: $ot, nightDiffMinutes: $nd);
}

it('computes a monthly-rated payslip (hand-computed fixture)', function () {
    // ₱30,000/month. One absence, 30 minutes late, 2h OT, 8h on a regular holiday.
    $result = payslipCalculator()->compute(new PayslipInput(
        monthlyRated: true,
        basicRate: Money::ofPesos(30000),
        days: [
            workday('2026-10-01'),
            workday('2026-10-02', worked: 450, late: 30),
            workday('2026-10-05', ot: 120),
            new DayData('2026-10-06', DayData::ABSENT),
            new DayData('2026-10-07', DayData::PRESENT, holiday: 'regular', workedMinutes: 480),
            new DayData('2026-10-10', DayData::REST_DAY, isRestDay: true),
        ],
    ));

    expect($result->dailyRate->toDecimal())->toBe('1379.31')                 // 30,000 × 12 ÷ 261
        ->and($result->amountOf('BASIC')->toDecimal())->toBe('15000.00')
        ->and($result->amountOf('ABSENCES')->toDecimal())->toBe('-1379.31')
        ->and($result->amountOf('TARDINESS')->toDecimal())->toBe('-86.21')    // 30/480 of a day
        ->and($result->amountOf('OT_REGULAR')->toDecimal())->toBe('431.03')   // 2h × 125%
        ->and($result->amountOf('PREMIUM_REGULAR_HOLIDAY')->toDecimal())->toBe('1379.31') // extra 100%
        ->and($result->grossPay->toDecimal())->toBe('15344.82')
        ->and($result->amountOf('SSS')->toDecimal())->toBe('750.00')
        ->and($result->amountOf('PHILHEALTH')->toDecimal())->toBe('375.00')
        ->and($result->amountOf('PAGIBIG')->toDecimal())->toBe('100.00')
        ->and($result->taxableIncome->toDecimal())->toBe('14119.82')
        ->and($result->amountOf('TAX')->toDecimal())->toBe('555.42')
        ->and($result->totalDeductions->toDecimal())->toBe('1780.42')
        ->and($result->netPay->toDecimal())->toBe('13564.40')
        ->and($result->employerContributions->toDecimal())->toBe('1990.00')
        ->and($result->attendance['days_absent'])->toBe(1)
        ->and($result->warnings)->toBe([]);
});

it('computes a daily-rated payslip (hand-computed fixture)', function () {
    // ₱645/day: 3 days worked (1h OT), 8h rest day work, unworked regular
    // holiday, a paid leave and an absence.
    $result = payslipCalculator()->compute(new PayslipInput(
        monthlyRated: false,
        basicRate: Money::ofPesos(645),
        days: [
            workday('2026-10-01'),
            workday('2026-10-02', ot: 60),
            workday('2026-10-05'),
            new DayData('2026-10-03', DayData::PRESENT, isRestDay: true, workedMinutes: 480),
            new DayData('2026-10-06', DayData::HOLIDAY, holiday: 'regular'),
            new DayData('2026-10-07', DayData::LEAVE, paidLeave: true),
            new DayData('2026-10-08', DayData::ABSENT),
        ],
    ));

    expect($result->amountOf('BASIC')->toDecimal())->toBe('1935.00')
        ->and($result->amountOf('OT_REGULAR')->toDecimal())->toBe('100.78')
        ->and($result->amountOf('PREMIUM_REST_DAY')->toDecimal())->toBe('838.50')
        ->and($result->amountOf('HOLIDAY_PAY')->toDecimal())->toBe('645.00')
        ->and($result->amountOf('PAID_LEAVE')->toDecimal())->toBe('645.00')
        ->and($result->grossPay->toDecimal())->toBe('4164.28')
        ->and($result->amountOf('SSS')->toDecimal())->toBe('350.00')           // MSC 14,000
        ->and($result->amountOf('PHILHEALTH')->toDecimal())->toBe('175.36')    // 5% of 14,028.75 ÷ 2 ÷ 2
        ->and($result->amountOf('PAGIBIG')->toDecimal())->toBe('100.00')
        ->and($result->amountOf('TAX')->toDecimal())->toBe('0.00')
        ->and($result->netPay->toDecimal())->toBe('3538.92')
        ->and($result->employerContributions->toDecimal())->toBe('980.36');
});

it('pays premiums for special days, holidays on rest days and night work', function () {
    $result = payslipCalculator()->compute(new PayslipInput(
        monthlyRated: false,
        basicRate: Money::ofPesos(800), // ₱100/hour
        days: [
            new DayData('2026-11-01', DayData::PRESENT, isRestDay: true, holiday: 'special_non_working', workedMinutes: 480),
            new DayData('2026-11-30', DayData::PRESENT, holiday: 'regular', workedMinutes: 480, overtimeMinutes: 60),
            new DayData('2026-12-27', DayData::PRESENT, isRestDay: true, holiday: 'regular', workedMinutes: 60),
            workday('2026-12-01', nd: 120),
        ],
    ));

    expect($result->amountOf('PREMIUM_SPECIAL_REST')->toDecimal())->toBe('1200.00')         // 8h × 150%
        ->and($result->amountOf('PREMIUM_REGULAR_HOLIDAY')->toDecimal())->toBe('1600.00')   // 8h × 200%
        ->and($result->amountOf('OT_REGULAR_HOLIDAY')->toDecimal())->toBe('260.00')         // 1h × 200% × 130%
        ->and($result->amountOf('PREMIUM_REGULAR_HOLIDAY_REST')->toDecimal())->toBe('260.00') // 1h × 260%
        ->and($result->amountOf('NIGHT_DIFF')->toDecimal())->toBe('20.00');                 // 2h × 10%
});

it('applies adjustments, keeping non-taxable allowances out of taxable income', function () {
    $result = payslipCalculator()->compute(new PayslipInput(
        monthlyRated: true,
        basicRate: Money::ofPesos(30000),
        days: [workday('2026-10-01')],
        adjustments: [
            ['kind' => PayslipLine::EARNING, 'label' => 'Rice subsidy', 'amount' => Money::ofPesos(1000), 'taxable' => false],
            ['kind' => PayslipLine::EARNING, 'label' => 'Performance bonus', 'amount' => Money::ofPesos(2000), 'taxable' => true],
            ['kind' => PayslipLine::DEDUCTION, 'label' => 'Cash advance', 'amount' => Money::ofPesos(500), 'taxable' => false],
        ],
    ));

    expect($result->grossPay->toDecimal())->toBe('18000.00')
        ->and($result->taxableIncome->toDecimal())->toBe('15775.00') // 17,000 - 1,225 contributions
        ->and($result->amountOf('OTHER_DEDUCTION')->toDecimal())->toBe('500.00')
        ->and($result->amountOf('TAX')->toDecimal())->toBe('803.70'); // 15% × (15,775 - 10,417)
});

it('treats incomplete punches as unpaid and warns about them', function () {
    $result = payslipCalculator()->compute(new PayslipInput(
        monthlyRated: true,
        basicRate: Money::ofPesos(26100),
        days: [new DayData('2026-10-01', DayData::INCOMPLETE)],
    ));

    expect($result->amountOf('ABSENCES')->toDecimal())->toBe('-1200.00')
        ->and($result->warnings)->toHaveCount(1);
});

it('exempts the statutory wages of minimum wage earners from tax', function () {
    // ₱695/day, 8 regular days with 2h OT, 8h rest day work, plus a ₱5,000 taxable allowance.
    $input = fn (bool $mwe) => new PayslipInput(
        monthlyRated: false,
        basicRate: Money::ofPesos(695),
        days: [
            ...array_map(fn ($d) => workday("2026-10-0{$d}", ot: $d === 1 ? 120 : 0), range(1, 8)),
            new DayData('2026-10-10', DayData::PRESENT, isRestDay: true, workedMinutes: 480),
        ],
        adjustments: [['kind' => PayslipLine::EARNING, 'label' => 'Performance bonus', 'amount' => Money::ofPesos(10000), 'taxable' => true]],
        minimumWageEarner: $mwe,
    );

    $regular = payslipCalculator()->compute($input(false));
    $mwe = payslipCalculator()->compute($input(true));

    // Gross is identical; only the tax treatment differs.
    expect($mwe->grossPay->equals($regular->grossPay))->toBeTrue()
        ->and($mwe->taxableIncome->toDecimal())->toBe('10000.00')      // only the bonus
        ->and($mwe->amountOf('TAX')->toDecimal())->toBe('0.00')        // below the ₱10,417 threshold
        ->and($regular->taxableIncome->isGreaterThan($mwe->taxableIncome))->toBeTrue()
        ->and($regular->amountOf('TAX')->isGreaterThan(Money::zero()))->toBeTrue();
});

it('taxes other income of minimum wage earners above the threshold', function () {
    $result = payslipCalculator()->compute(new PayslipInput(
        monthlyRated: false,
        basicRate: Money::ofPesos(695),
        days: [workday('2026-10-01')],
        adjustments: [['kind' => PayslipLine::EARNING, 'label' => 'Commission', 'amount' => Money::ofPesos(20000), 'taxable' => true]],
        minimumWageEarner: true,
    ));

    expect($result->taxableIncome->toDecimal())->toBe('20000.00')
        ->and($result->amountOf('TAX')->toDecimal())->toBe('1604.10'); // 937.50 + 20% × (20,000 − 16,667)
});

it('pays half-day leaves as half paid leave and half work', function () {
    $days = [
        new DayData('2026-10-01', DayData::PRESENT, workedMinutes: 240, paidLeave: true, leaveFraction: 0.5),  // worked the other half
        new DayData('2026-10-02', DayData::ABSENT, paidLeave: true, leaveFraction: 0.5),                      // skipped the other half
    ];

    $daily = payslipCalculator()->compute(new PayslipInput(monthlyRated: false, basicRate: Money::ofPesos(800), days: $days));
    $monthly = payslipCalculator()->compute(new PayslipInput(monthlyRated: true, basicRate: Money::ofPesos(26100), days: $days));

    expect($daily->amountOf('BASIC')->toDecimal())->toBe('400.00')        // 4h worked
        ->and($daily->amountOf('PAID_LEAVE')->toDecimal())->toBe('800.00') // two half days
        ->and($daily->attendance['paid_leave_days'])->toBe(1.0)
        ->and($monthly->amountOf('ABSENCES')->toDecimal())->toBe('-600.00') // only the unworked half of Oct 2 (daily 1,200)
        ->and($monthly->amountOf('TARDINESS')->isZero())->toBeTrue();
});
