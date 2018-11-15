<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;

/**
 * Class CleanUp
 *
 * @package MyForksFiles\CliPack\Commands
 * @author myForksFiles(at)gmail.com
 * @category CLI Laravel clear tools
 *
 *- -***
 */
class CleanUp extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'dev:clean';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Clean tmp files, logs, storage.';

    /**
     * current environment value
     * @var string
     */
    protected $env;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Call all clear artisan command');
        $this->comment('clear: compiled, cache, config, route, view');
        $this->call('clear-compiled');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
    }
}
