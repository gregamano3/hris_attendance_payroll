<?php

namespace App\Features\Payroll\Queries;

use App\Features\Payroll\Compute\PagIbigCalculator;
use App\Features\Payroll\Compute\PayslipCalculator;
use App\Features\Payroll\Compute\PhilHealthCalculator;
use App\Features\Payroll\Compute\SssCalculator;
use App\Features\Payroll\Compute\WithholdingTaxCalculator;
use App\Features\Payroll\Enums\StatutoryScheme;
use App\Features\Payroll\Models\StatutoryRate;
use App\Features\Payroll\Models\TaxBracket;
use App\Shared\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Statutory parameters effective on a date. All versions are cached forever
 * and flushed when an administrator changes them.
 */
class StatutoryRates
{
    public const CACHE_KEY = 'payroll:statutory-rates';

    /**
     * @return array<string, int|float|string>
     */
    public function parameters(StatutoryScheme $scheme, Carbon $date): array
    {
        $version = collect($this->all()['schemes'][$scheme->value] ?? [])
            ->filter(fn (array $v) => $v['effective_from'] <= $date->toDateString())
            ->sortByDesc('effective_from')
            ->first();

        if ($version === null) {
            throw new RuntimeException("No {$scheme->label()} rates are effective on {$date->toDateString()}.");
        }

        return $version['parameters'];
    }

    /**
     * @return list<array{lower: Money, upper: Money|null, base: Money, rate: float}>
     */
    public function taxBrackets(string $frequency, Carbon $date): array
    {
        $versions = collect($this->all()['tax'][$frequency] ?? [])
            ->filter(fn (array $v, string $from) => $from <= $date->toDateString())
            ->sortKeysDesc();

        $brackets = $versions->first();

        if ($brackets === null) {
            throw new RuntimeException("No {$frequency} withholding tax table is effective on {$date->toDateString()}.");
        }

        return array_map(fn (array $b) => [
            'lower' => Money::ofCentavos($b['lower']),
            'upper' => $b['upper'] === null ? null : Money::ofCentavos($b['upper']),
            'base' => Money::ofCentavos($b['base']),
            'rate' => (float) $b['rate'],
        ], $brackets);
    }

    /**
     * A payslip calculator wired with the rates effective on the given date.
     */
    public function calculatorFor(Carbon $date): PayslipCalculator
    {
        $config = config('hris.payroll');

        return new PayslipCalculator(
            new SssCalculator($this->parameters(StatutoryScheme::Sss, $date)),
            new PhilHealthCalculator($this->parameters(StatutoryScheme::PhilHealth, $date)),
            new PagIbigCalculator($this->parameters(StatutoryScheme::PagIbig, $date)),
            new WithholdingTaxCalculator($this->taxBrackets($config['tax_frequency'], $date)),
            daysPerYear: (int) $config['days_per_year'],
            hoursPerDay: (int) $config['hours_per_day'],
            contributionFraction: (float) $config['contribution_fraction'],
            multipliers: array_map('floatval', $config['multipliers']),
            overtimeRegular: (float) $config['overtime_regular'],
            overtimePremium: (float) $config['overtime_premium'],
            nightDifferential: (float) $config['night_differential'],
            annualTax: rescue(fn () => new WithholdingTaxCalculator($this->taxBrackets('annual', $date)), null, false),
        );
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{schemes: array<string, list<array{effective_from: string, parameters: array<string, int|float|string>}>>, tax: array<string, array<string, list<array{lower: int, upper: int|null, base: int, rate: string}>>>}
     */
    private function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $schemes = [];

            foreach (StatutoryRate::query()->orderBy('effective_from')->get() as $rate) {
                $schemes[$rate->scheme->value][] = [
                    'effective_from' => $rate->effective_from->toDateString(),
                    'parameters' => $rate->parameters,
                ];
            }

            $tax = [];

            foreach (TaxBracket::query()->orderBy('lower_bound')->get() as $bracket) {
                $tax[$bracket->frequency][$bracket->effective_from->toDateString()][] = [
                    'lower' => $bracket->lower_bound->centavos,
                    'upper' => $bracket->upper_bound?->centavos,
                    'base' => $bracket->base_tax->centavos,
                    'rate' => $bracket->rate,
                ];
            }

            return ['schemes' => $schemes, 'tax' => $tax];
        });
    }
}
