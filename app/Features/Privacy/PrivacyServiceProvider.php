<?php

namespace App\Features\Privacy;

use App\Features\Privacy\Anonymize\AnonymizeCommand;
use App\Shared\Providers\FeatureServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class PrivacyServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        $this->commands([AnonymizeCommand::class]);
        $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $schedule
            ->command('privacy:anonymize')->monthlyOn(1, '03:00')->withoutOverlapping());
    }
}
