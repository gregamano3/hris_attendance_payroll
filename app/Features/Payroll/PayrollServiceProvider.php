<?php

namespace App\Features\Payroll;

use App\Features\Payroll\Models\StatutoryRate;
use App\Features\Payroll\Models\TaxBracket;
use App\Features\Payroll\Queries\StatutoryRates;
use App\Shared\Providers\FeatureServiceProvider;

class PayrollServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        foreach ([StatutoryRate::class, TaxBracket::class] as $model) {
            $model::saved(fn () => StatutoryRates::flush());
            $model::deleted(fn () => StatutoryRates::flush());
        }
    }
}
