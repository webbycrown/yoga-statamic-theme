<?php

namespace App\Providers;

use App\Console\Commands\SetupCatalogueCommand;
use Illuminate\Support\ServiceProvider;

class YogaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SetupCatalogueCommand::class]);
        }
    }
}
