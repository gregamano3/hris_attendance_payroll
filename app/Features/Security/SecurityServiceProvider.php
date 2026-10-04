<?php

namespace App\Features\Security;

use App\Features\Security\Reencrypt\ReencryptCommand;
use App\Shared\Providers\FeatureServiceProvider;

class SecurityServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        $this->commands([ReencryptCommand::class]);
    }
}
