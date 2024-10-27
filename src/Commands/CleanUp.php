<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanUp alias for ArtisanClearAll
 *
 * @package MyForksFiles\CliPack\Commands
 * @author myForksFiles(at)gmail.com
 * @category CLI Laravel clear tools
 *
 *- -***
 */
class CleanUp extends ArtisanClearAll
{
    protected $signature = 'cleanup';

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
