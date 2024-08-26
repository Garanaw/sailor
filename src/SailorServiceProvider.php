<?php

declare(strict_types=1);

namespace Garanaw\Sailor;

use Garanaw\Sailor\Console\InstallCommand;
use Garanaw\Sailor\Console\PublishCommand;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;

class SailorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->about();
        $this->registerCommands();
        $this->configurePublishing();
    }

    protected function about(): void
    {
        AboutCommand::add('Sailor', [
            'version' => '1.0.0',
            'by' => 'Garanaw',
            'description' => 'An extension to Laravel Sail',
        ]);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                PublishCommand::class,
            ]);
        }
    }

    protected function configurePublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../runtimes' => $this->app->basePath('docker'),
            ], ['sailor', 'sailor-docker']);

            //$this->publishes([
            //    __DIR__ . '/../bin/sail' => $this->app->basePath('sail'),
            //], ['sailor', 'sailor-bin']);
            //
            //$this->publishes([
            //    __DIR__ . '/../database' => $this->app->basePath('docker'),
            //], ['sailor', 'sailor-database']);
        }
    }
}
