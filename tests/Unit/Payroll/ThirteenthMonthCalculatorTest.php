<?php

use App\Features\Payroll\Compute\ThirteenthMonthCalculator;
use App\Shared\Money\Money;

function thirteenth(array $basics, ?string $used = null): array
{
    return (new ThirteenthMonthCalculator(Money::ofPesos(90000)))->compute(
        array_map(fn ($b) => Money::ofPesos($b), $basics),
        $used === null ? null : Money::ofPesos($used),
    );
}

it('is one twelfth of the basic salary earned in the year', function () {
    // ₱30,000/month for a full year = 24 cutoffs of ₱15,000, one absence (₱1,379.31).
    $result = thirteenth([...array_fill(0, 24, '15000'), '-1379.31']);

    expect($result['basic']->toDecimal())->toBe('358620.69')
        ->and($result['amount']->toDecimal())->toBe('29885.06')
        ->and($result['exempt']->toDecimal())->toBe('29885.06')
        ->and($result['taxable']->toDecimal())->toBe('0.00');
});

it('pro-rates mid-year hires', function () {
    // Hired in July: 12 cutoffs of ₱10,000.
    expect(thirteenth(array_fill(0, 12, '10000'))['amount']->toDecimal())->toBe('10000.00');
});

it('taxes the excess over the ₱90,000 ceiling', function () {
    // ₱150,000/month: 13th month ₱150,000, ₱60,000 taxable.
    $result = thirteenth(array_fill(0, 24, '75000'));

    expect($result['amount']->toDecimal())->toBe('150000.00')
        ->and($result['exempt']->toDecimal())->toBe('90000.00')
        ->and($result['taxable']->toDecimal())->toBe('60000.00');
});

it('accounts for exemption already used this year', function () {
    $result = thirteenth(array_fill(0, 24, '50000'), used: '50000');

    expect($result['amount']->toDecimal())->toBe('100000.00')
        ->and($result['exempt']->toDecimal())->toBe('40000.00')
        ->and($result['taxable']->toDecimal())->toBe('60000.00');
});

it('never goes negative', function () {
    expect(thirteenth(['-500'])['amount']->toDecimal())->toBe('0.00');
});
