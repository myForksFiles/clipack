<?php

namespace MyForksFiles\CliPack;

use Illuminate\Support\ServiceProvider;

/**
 * This is the service provider.
 *
 * Place the line below in the providers array inside app/config/app.php
 * <code>'MyForksFiles\CliPack\CliPackServiceProvider::class',</code>
 *
 * @package CliPack
 *
 *- -***
 **/
class CliPackServiceProvider extends ServiceProvider
{
    use CliPackTools;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * @var array
     */
    protected $commands = [
        'MyForksFiles\CliPack\Commands\DbDumper',
        'MyForksFiles\CliPack\Commands\DbImporter',
        'MyForksFiles\CliPack\Commands\DevLog',
        'MyForksFiles\CliPack\Commands\RunPhp',
        'MyForksFiles\CliPack\Commands\ScheduleList',
        'MyForksFiles\CliPack\Commands\SetAuthBasic',
//        'MyForksFiles\CliPack\Commands\GetConfig',
//        'MyForksFiles\CliPack\Commands\LastLogs',
//        'MyForksFiles\CliPack\Commands\UsageStatus',
        'MyForksFiles\CliPack\Commands\ExportLang',
//        'MyForksFiles\CliPack\Commands\ChangeUrl',
        'MyForksFiles\CliPack\Commands\CleanUp',
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //config
        $this->mergeConfigFrom(
            __DIR__ . '/config/app.php',
            'packages.MyForksFiles.CliPack.app'
        );

        $kernel = $this->app['Illuminate\Contracts\Http\Kernel'];

        if (CliPackTools::checkAuthBasicStatus()) {
            $kernel->pushMiddleware('MyForksFiles\CliPack\Http\Middleware\AuthBasic');
        }
    }

    /**
     * Register the command.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
        $this->app->singleton(DevStatusFacade::class, function () {
            return new CliPackFacade();
        });
        $this->app->alias(CliPackFacade::class, 'CliPack');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [__CLASS__];
    }
}
