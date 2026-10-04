<?php

use App\Features\Payroll\Enums\StatutoryScheme;
use App\Features\Payroll\Models\StatutoryRate;
use App\Features\Payroll\Queries\StatutoryRates;
use App\Shared\Authorization\Role;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed(PayrollSeeder::class);
    $this->officer = userWithRole(Role::Payroll);
});

it('shows the statutory tables', function () {
    $this->actingAs($this->officer)->get('/payroll/statutory-rates')->assertOk()->assertSee('PhilHealth')->assertSee('333,333.00');
});

it('adds a new effective-dated version and serves it from the refreshed cache', function () {
    $rates = app(StatutoryRates::class);
    expect($rates->parameters(StatutoryScheme::PhilHealth, Carbon::parse('2027-02-01'))['rate'])->toBe(0.05);

    $this->actingAs($this->officer)->post('/payroll/statutory-rates', [
        'scheme' => 'philhealth', 'effective_from' => '2027-01-01',
        'parameters' => ['rate' => '0.055', 'floor' => '10000', 'ceiling' => '100000', 'ee_share' => '0.5'],
    ])->assertSessionHas('success');

    expect($rates->parameters(StatutoryScheme::PhilHealth, Carbon::parse('2027-02-01'))['rate'])->toBe(0.055)
        ->and($rates->parameters(StatutoryScheme::PhilHealth, Carbon::parse('2026-12-31'))['rate'])->toBe(0.05)
        ->and(StatutoryRate::query()->count())->toBe(4);
});

it('validates scheme parameters', function () {
    $this->actingAs($this->officer)->post('/payroll/statutory-rates', [
        'scheme' => 'sss', 'effective_from' => '2025-01-01', 'parameters' => ['ee_rate' => '0.05'],
    ])->assertSessionHasErrors(['effective_from', 'parameters.er_rate']);
});

it('adds a withholding tax table', function () {
    $this->actingAs($this->officer)->post('/payroll/statutory-rates/tax', [
        'frequency' => 'semi_monthly', 'effective_from' => '2027-01-01',
        'brackets' => [
            ['lower' => '0', 'upper' => '12499.99', 'base' => '0', 'rate' => '0'],
            ['lower' => '12500', 'upper' => '', 'base' => '0', 'rate' => '0.15'],
            ['lower' => '', 'upper' => '', 'base' => '', 'rate' => ''],
        ],
    ])->assertSessionHas('success');

    expect(app(StatutoryRates::class)->taxBrackets('semi_monthly', Carbon::parse('2027-03-01')))->toHaveCount(2);
});

it('restricts statutory rates to settings managers', function () {
    $this->actingAs(userWithRole(Role::Hr))->get('/payroll/statutory-rates')->assertForbidden();
});
