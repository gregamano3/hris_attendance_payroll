<?php

use App\Shared\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

it('builds semi-monthly cutoffs', function () {
    $first = Period::semiMonthlyContaining(Carbon::parse('2026-02-10'));
    $second = Period::semiMonthlyContaining(Carbon::parse('2026-02-20'));

    expect($first->from->toDateString())->toBe('2026-02-01')
        ->and($first->to->toDateString())->toBe('2026-02-15')
        ->and($second->from->toDateString())->toBe('2026-02-16')
        ->and($second->to->toDateString())->toBe('2026-02-28')
        ->and($second->days())->toBe(13)
        ->and($first->label())->toBe('Feb 1–15, 2026');
});

it('reads and bounds periods from a request', function () {
    $period = Period::fromRequest(Request::create('/', 'GET', ['from' => '2026-01-01', 'to' => '2026-12-31']));

    expect($period->days())->toBe(62);

    $fallback = Period::fromRequest(Request::create('/', 'GET', ['from' => 'nonsense']), Carbon::parse('2026-03-20'));
    expect($fallback->from->toDateString())->toBe('2026-03-16');
});

it('rejects inverted periods', function () {
    new Period(Carbon::parse('2026-01-02'), Carbon::parse('2026-01-01'));
})->throws(InvalidArgumentException::class);
