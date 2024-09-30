<?php

namespace Ogilo\ExportRoutes\src;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register any bindings if necessary
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportRoutesToCsv::class,
            ]);
        }
    }
}
