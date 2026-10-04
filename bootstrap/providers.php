<?php

use App\Features\Auth\AuthServiceProvider;
use App\Features\Dashboard\DashboardServiceProvider;
use App\Features\Health\HealthServiceProvider;
use App\Features\Users\UsersServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Feature slices
    AuthServiceProvider::class,
    DashboardServiceProvider::class,
    HealthServiceProvider::class,
    UsersServiceProvider::class,
];
