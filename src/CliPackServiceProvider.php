<?php
namespace MyForksFiles\CliPack;

use Illuminate\Support\ServiceProvider;
use File;
use Config;

/**
 * This is the service provider.
 *
 * Place the line below in the providers array inside app/config/app.php
 * <code>'MyForksFiles\CliPack\CliPackServiceProvider::class',</code>
 *
 * @package CliPack
 * @author MyForksFiles
 *
 **/
class CliPackServiceProvider extends ServiceProvider
{
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
//        'MyForksFiles\CliPack\Commands\DbDumper',
//        'MyForksFiles\CliPack\Commands\DbImporter',
        'MyForksFiles\CliPack\Commands\DevLog',
//        'MyForksFiles\CliPack\Commands\GetConfig',
        'MyForksFiles\CliPack\Commands\RunPhp',
        'MyForksFiles\CliPack\Commands\ScheduleList',
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $kernel = $this->app['Illuminate\Contracts\Http\Kernel'];

        if ($this->checkAuthBasicStatus()) {
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

        //config
        $this->mergeConfigFrom(
            __DIR__.'/config/app.php', 'packages.MyForksFiles.CliPack.app'
        );
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

    /**
     * @return bool
     */
    protected function checkAuthBasicStatus()
    {
        $authBasicFile = Config::get('packages.MyForksFiles.CliPack.app.fileAuthBasicProtection');
        $authBasicFile = storage_path($authBasicFile);

        return (File::exists($authBasicFile)) ? true : false;
    }
}