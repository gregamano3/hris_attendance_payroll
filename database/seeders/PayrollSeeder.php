<?php

namespace Database\Seeders;

use App\Features\Payroll\Enums\StatutoryScheme;
use App\Features\Payroll\Models\DeMinimisBenefit;
use App\Features\Payroll\Models\StatutoryRate;
use App\Features\Payroll\Models\TaxBracket;
use App\Shared\Money\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Philippine statutory contribution and tax parameters.
 *
 * These are the published rates at the time of writing. They can be changed
 * at any time from Payroll > Statutory rates by adding a new effective-dated
 * version; always verify against the latest SSS, PhilHealth, Pag-IBIG and
 * BIR circulars.
 */
class PayrollSeeder extends Seeder
{
    /** SSS: 15% of the MSC (5% employee, 10% employer), MSC ₱5,000–₱35,000, RA 11199 schedule from January 2025. */
    public const SSS_2025 = [
        'ee_rate' => 0.05, 'er_rate' => 0.10,
        'msc_min' => 5000, 'msc_max' => 35000, 'msc_step' => 500,
        'ec_threshold' => 15000, 'ec_low' => 10, 'ec_high' => 30,
    ];

    /** PhilHealth: 5% premium on ₱10,000–₱100,000, shared equally (from 2024). */
    public const PHILHEALTH_2025 = [
        'rate' => 0.05, 'floor' => 10000, 'ceiling' => 100000, 'ee_share' => 0.5,
    ];

    /** Pag-IBIG: 2% / 2% on up to ₱10,000 (1% employee share at ₱1,500 and below), from February 2024. */
    public const PAGIBIG_2024 = [
        'ee_rate' => 0.02, 'ee_rate_low' => 0.01, 'low_threshold' => 1500, 'er_rate' => 0.02, 'max_base' => 10000,
    ];

    /**
     * BIR withholding tax (TRAIN law, 2023 onwards): [lower, upper, base tax, rate on excess over lower].
     *
     * @var list<array{0: string, 1: string|null, 2: string, 3: float}>
     */
    public const TAX_SEMI_MONTHLY_2023 = [
        ['0', '10416.99', '0', 0.0],
        ['10417', '16666.99', '0', 0.15],
        ['16667', '33332.99', '937.50', 0.20],
        ['33333', '83332.99', '4270.70', 0.25],
        ['83333', '333332.99', '16770.70', 0.30],
        ['333333', null, '91770.70', 0.35],
    ];

    /**
     * @var list<array{0: string, 1: string|null, 2: string, 3: float}>
     */
    public const TAX_MONTHLY_2023 = [
        ['0', '20832.99', '0', 0.0],
        ['20833', '33332.99', '0', 0.15],
        ['33333', '66666.99', '1875', 0.20],
        ['66667', '166666.99', '8541.80', 0.25],
        ['166667', '666666.99', '33541.80', 0.30],
        ['666667', null, '183541.80', 0.35],
    ];

    /**
     * Annual income tax table (TRAIN law, 2023 onwards), used for the
     * year-end annualization on BIR Form 2316 and the alphalist.
     *
     * @var list<array{0: string, 1: string|null, 2: string, 3: float}>
     */
    public const TAX_ANNUAL_2023 = [
        ['0', '250000', '0', 0.0],
        ['250000', '400000', '0', 0.15],
        ['400000', '800000', '22500', 0.20],
        ['800000', '2000000', '102500', 0.25],
        ['2000000', '8000000', '402500', 0.30],
        ['8000000', null, '2202500', 0.35],
    ];

    /**
     * @var list<array{0: string, 1: string|null, 2: string, 3: float}>
     */
    public const TAX_WEEKLY_2023 = [
        ['0', '4807.99', '0', 0.0],
        ['4808', '7691.99', '0', 0.15],
        ['7692', '15384.99', '432.60', 0.20],
        ['15385', '38461.99', '1971.20', 0.25],
        ['38462', '153845.99', '7740.45', 0.30],
        ['153846', null, '42355.65', 0.35],
    ];

    /**
     * De minimis benefit ceilings (RR 11-2018). The BIR revises these; edit
     * them under Payroll → De minimis benefits.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string}>
     */
    public const DE_MINIMIS = [
        ['RICE', 'Rice subsidy', '2000', 'monthly'],
        ['UNIFORM', 'Uniform and clothing allowance', '6000', 'annual'],
        ['MEDICAL_CASH', 'Medical cash allowance to dependents', '250', 'monthly'],
        ['LAUNDRY', 'Laundry allowance', '300', 'monthly'],
        ['MEDICAL_ASSISTANCE', 'Actual medical assistance', '10000', 'annual'],
        ['ACHIEVEMENT', 'Employee achievement awards', '10000', 'annual'],
        ['CHRISTMAS', 'Christmas / anniversary gifts', '5000', 'annual'],
        ['CBA', 'CBA and productivity incentive benefits', '10000', 'annual'],
    ];

    public function run(): void
    {
        $schemes = [
            [StatutoryScheme::Sss, '2025-01-01', self::SSS_2025],
            [StatutoryScheme::PhilHealth, '2024-01-01', self::PHILHEALTH_2025],
            [StatutoryScheme::PagIbig, '2024-02-01', self::PAGIBIG_2024],
        ];

        foreach ($schemes as [$scheme, $from, $parameters]) {
            StatutoryRate::query()->firstOrCreate(
                ['scheme' => $scheme, 'effective_from' => $from],
                ['parameters' => $parameters],
            );
        }

        $tables = [
            'semi_monthly' => self::TAX_SEMI_MONTHLY_2023,
            'monthly' => self::TAX_MONTHLY_2023,
            'annual' => self::TAX_ANNUAL_2023,
            'weekly' => self::TAX_WEEKLY_2023,
        ];

        foreach ($tables as $frequency => $brackets) {
            if (TaxBracket::query()->where('frequency', $frequency)->exists()) {
                continue;
            }

            foreach ($brackets as [$lower, $upper, $base, $rate]) {
                TaxBracket::query()->create([
                    'effective_from' => '2023-01-01',
                    'frequency' => $frequency,
                    'lower_bound' => Money::ofPesos($lower),
                    'upper_bound' => $upper === null ? null : Money::ofPesos($upper),
                    'base_tax' => Money::ofPesos($base),
                    'rate' => $rate,
                ]);
            }
        }

        if (! Schema::hasTable('de_minimis_benefits')) {
            return; // older migration calling the seeder before the table exists
        }

        foreach (self::DE_MINIMIS as [$code, $name, $limit, $period]) {
            DeMinimisBenefit::query()->firstOrCreate(['code' => $code], ['name' => $name, 'limit_amount' => Money::ofPesos($limit), 'period' => $period]);
        }
    }
}
