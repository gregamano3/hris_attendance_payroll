<?php

use App\Features\Health\HealthServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Feature slices
    HealthServiceProvider::class,
];
