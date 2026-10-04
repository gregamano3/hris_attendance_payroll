<?php

use App\Features\Payroll\Compute\PagIbigCalculator;
use App\Features\Payroll\Compute\PhilHealthCalculator;
use App\Features\Payroll\Compute\SssCalculator;
use App\Features\Payroll\Compute\WithholdingTaxCalculator;
use App\Shared\Money\Money;
use Database\Seeders\PayrollSeeder;

function sss(): SssCalculator
{
    return new SssCalculator(PayrollSeeder::SSS_2025);
}

it('computes SSS from the salary credit brackets', function (string $salary, string $msc, string $ee, string $er, string $ec) {
    $result = sss()->monthly(Money::ofPesos($salary));

    expect($result['msc']->toDecimal())->toBe($msc)
        ->and($result['employee']->toDecimal())->toBe($ee)
        ->and($result['employer']->toDecimal())->toBe($er)
        ->and($result['ec']->toDecimal())->toBe($ec);
})->with([
    'below minimum' => ['4000', '5000.00', '250.00', '500.00', '10.00'],
    'bracket lower edge' => ['5250', '5500.00', '275.00', '550.00', '10.00'],
    'bracket upper edge' => ['5749.99', '5500.00', '275.00', '550.00', '10.00'],
    'EC switches at 15,000' => ['14750', '15000.00', '750.00', '1500.00', '30.00'],
    'mid' => ['30000', '30000.00', '1500.00', '3000.00', '30.00'],
    'capped' => ['80000', '35000.00', '1750.00', '3500.00', '30.00'],
]);

it('computes PhilHealth with floor and ceiling', function (string $salary, string $premium, string $ee) {
    $result = (new PhilHealthCalculator(PayrollSeeder::PHILHEALTH_2025))->monthly(Money::ofPesos($salary));

    expect($result['premium']->toDecimal())->toBe($premium)
        ->and($result['employee']->toDecimal())->toBe($ee)
        ->and($result['employer']->toDecimal())->toBe($ee);
})->with([
    'floor' => ['8000', '500.00', '250.00'],
    'mid' => ['30000', '1500.00', '750.00'],
    'ceiling' => ['150000', '5000.00', '2500.00'],
]);

it('computes Pag-IBIG with the low-income rate and cap', function (string $salary, string $ee, string $er) {
    $result = (new PagIbigCalculator(PayrollSeeder::PAGIBIG_2024))->monthly(Money::ofPesos($salary));

    expect($result['employee']->toDecimal())->toBe($ee)->and($result['employer']->toDecimal())->toBe($er);
})->with([
    'low income' => ['1500', '15.00', '30.00'],
    'regular' => ['8000', '160.00', '160.00'],
    'capped' => ['30000', '200.00', '200.00'],
]);

it('computes semi-monthly withholding tax per the TRAIN table', function (string $taxable, string $tax) {
    $brackets = array_map(fn (array $b) => [
        'lower' => Money::ofPesos($b[0]),
        'upper' => $b[1] === null ? null : Money::ofPesos($b[1]),
        'base' => Money::ofPesos($b[2]),
        'rate' => $b[3],
    ], PayrollSeeder::TAX_SEMI_MONTHLY_2023);

    expect((new WithholdingTaxCalculator($brackets))->compute(Money::ofPesos($taxable))->toDecimal())->toBe($tax);
})->with([
    'exempt' => ['10417', '0.00'],
    'second bracket' => ['14119.82', '555.42'],
    'third bracket' => ['20000', '1604.10'],
    'fourth bracket' => ['50000', '8437.45'],
    'top bracket' => ['400000', '115104.15'],
    'zero' => ['0', '0.00'],
]);

it('computes annual income tax per the TRAIN table', function (string $taxable, string $tax) {
    $brackets = array_map(fn (array $b) => [
        'lower' => Money::ofPesos($b[0]),
        'upper' => $b[1] === null ? null : Money::ofPesos($b[1]),
        'base' => Money::ofPesos($b[2]),
        'rate' => $b[3],
    ], PayrollSeeder::TAX_ANNUAL_2023);

    expect((new WithholdingTaxCalculator($brackets))->compute(Money::ofPesos($taxable))->toDecimal())->toBe($tax);
})->with([
    'exempt' => ['250000', '0.00'],
    'second' => ['300000', '7500.00'],
    'third' => ['500000', '42500.00'],
    'fourth' => ['1000000', '152500.00'],
    'fifth' => ['3000000', '702500.00'],
    'top' => ['10000000', '2902500.00'],
]);
