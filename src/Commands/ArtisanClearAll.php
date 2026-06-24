<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanUp
 *
 * @author myForksFiles(at)gmail.com
 *
 * @category CLI Laravel clear tools
 *
 *- -***
 */
class ArtisanClearAll extends Command
{
    protected $signature = 'mff:clean:up';

    protected $description = 'artisan clear commands';

    public function handle()
    {
        $this->info('Call all clear artisan command', OutputInterface::VERBOSITY_VERBOSE);

        foreach ([
            'cache:clear',
            'clear-compiled',
            'config:cache',
            'config:clear',
            'optimize:clear',
            'route:clear',
            'schedule:clear-cache',
            'view:clear',
            //                'telescope:clear',
            //                'telescope:prune',
        ] as $command
        ) {
            $this->info('Call all clear artisan command', OutputInterface::VERBOSITY_VERY_VERBOSE);
            $this->call($command);
        }

        return Command::SUCCESS;
    }
}
