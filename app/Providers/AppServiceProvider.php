<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Paginator::useBootstrapFive();

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->mixedCase()->numbers()->uncompromised()
            : Password::min(8));
    }
}
