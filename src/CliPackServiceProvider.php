<?php

namespace MyForksFiles\CliPack;

use Illuminate\Support\ServiceProvider;
use MyForksFiles\CliPack\Commands\CleanUp;
use MyForksFiles\CliPack\Commands\DbDumper;
use MyForksFiles\CliPack\Commands\DevLog;
use MyForksFiles\CliPack\Commands\RunPhp;
use MyForksFiles\CliPack\Commands\ScheduleList;
use MyForksFiles\CliPack\Commands\SetAuthBasic;

class CliPackServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/app.php',
            'clipack'
        );
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/lang', 'clipack');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CleanUp::class,
            DbDumper::class,
            DevLog::class,
            RunPhp::class,
            ScheduleList::class,
            SetAuthBasic::class,
        ]);

        $this->publishes([
            __DIR__.'/config/app.php' => config_path('clipack.php'),
        ], 'clipack-config');

        $this->publishes([
            __DIR__.'/lang' => $this->app->langPath('vendor/clipack'),
        ], 'clipack-lang');
    }

    /**
     * @return array<int, class-string>
     */
    #[\Override]
    public function provides(): array
    {
        return [self::class];
    }
}
