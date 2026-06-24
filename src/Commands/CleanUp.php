<?php

namespace MyForksFiles\CliPack\Commands;

class CleanUp extends ArtisanClearAll
{
    protected $signature = 'cleanup';

    #[\Override]
    public function handle(): int
    {
        $this->info('Call all clear artisan command');
        $this->comment('clear: compiled, cache, config, route, view');
        $this->call('clear-compiled');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        return self::SUCCESS;
    }
}
