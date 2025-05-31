<?php

namespace Mahfouz\Helpers;

use Illuminate\Support\ServiceProvider;
use Mahfouz\Helpers\Console\Commands\CleanupUnusedMedia;
use Mahfouz\Helpers\Console\Commands\MakeApiCommand;
use Mahfouz\Helpers\Console\Commands\MakeServiceCommand;

class MahfouzHelpersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeApiCommand::class,
                MakeServiceCommand::class,
                CleanupUnusedMedia::class,
            ]);

            // Publish stubs
            $this->publishes([
                __DIR__.'/../stubs' => $this->app->basePath('stubs/mahfouz'),
            ], 'mahfouz-stubs');
        }
    }
}