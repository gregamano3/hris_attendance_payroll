<?php

use App\Shared\Money\Money;

it('parses peso amounts into centavos', function (string|int|float $input, int $centavos) {
    expect(Money::ofPesos($input)->centavos)->toBe($centavos);
})->with([
    ['1,234.56', 123456],
    ['₱ 1,000', 100000],
    [1234.5, 123450],
    [25, 2500],
    ['0.005', 1],
    ['0.004', 0],
    ['-10.25', -1025],
]);

it('rejects invalid amounts', function () {
    Money::ofPesos('abc');
})->throws(InvalidArgumentException::class);

it('does arithmetic with half-up rounding', function () {
    $rate = Money::ofPesos('645.00');

    expect($rate->multipliedBy(1.25)->toDecimal())->toBe('806.25')
        ->and(Money::ofPesos('20000')->dividedBy(3)->toDecimal())->toBe('6666.67')
        ->and(Money::ofCentavos(5)->multipliedBy(0.5)->centavos)->toBe(3)
        ->and($rate->plus(Money::ofPesos(5))->minus(Money::ofPesos(50))->toDecimal())->toBe('600.00');
});

it('compares amounts', function () {
    $a = Money::ofPesos(10);
    $b = Money::ofPesos(20);

    expect($a->isLessThan($b))->toBeTrue()
        ->and($b->isGreaterThan($a))->toBeTrue()
        ->and($a->max($b))->toBe($b)
        ->and($a->min($b))->toBe($a)
        ->and(Money::zero()->isZero())->toBeTrue()
        ->and($a->equals(Money::ofCentavos(1000)))->toBeTrue();
});

it('formats amounts', function () {
    expect(Money::ofCentavos(123456789)->format())->toBe('₱1,234,567.89')
        ->and(Money::ofCentavos(-5050)->format())->toBe('-₱50.50')
        ->and(Money::ofCentavos(-5)->toDecimal())->toBe('-0.05')
        ->and((string) Money::ofPesos(1))->toBe('₱1.00');
});
