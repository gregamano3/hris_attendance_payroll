<?php

namespace App\Shared\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Base provider for a vertical slice feature living in app/Features/<Feature>.
 *
 * By convention it loads, when present:
 *  - routes.php  (wrapped in the "web" middleware group)
 *  - Views/      (registered under the kebab-cased feature namespace, e.g. "employees::index")
 */
abstract class FeatureServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $directory = $this->featurePath();

        if (is_file($directory.'/routes.php') && ! $this->app->routesAreCached()) {
            Route::middleware('web')->group($directory.'/routes.php');
        }

        if (is_dir($directory.'/Views')) {
            $this->loadViewsFrom($directory.'/Views', $this->featureNamespace());
        }

        $this->bootFeature();
    }

    /**
     * Hook for feature specific bootstrapping (policies, observers, listeners...).
     */
    protected function bootFeature(): void {}

    protected function featurePath(): string
    {
        return dirname((string) (new ReflectionClass(static::class))->getFileName());
    }

    protected function featureNamespace(): string
    {
        return Str::kebab(basename($this->featurePath()));
    }
}
