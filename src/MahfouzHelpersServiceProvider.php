<?php

namespace Mahfouz\Helpers;

use Illuminate\Support\ServiceProvider;
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
            ]);

            // Publish stubs
            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/mahfouz'),
            ], 'mahfouz-stubs');
        }
    }
}