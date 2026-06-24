<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;

class CleanAll extends Command
{
    protected $signature = 'clean';

    protected $description = 'artisan run all clear command';

    public function handle()
    {
        $artisanCommands = [
            //            'telescope:clear',
            //            'telescope:prune',
            // 'config:clear',
            // 'cache:clear',
            // 'view:clear',
            // 'route:clear',

            'cache:clear',
            'config:cache',
            'config:clear',
            'optimize:clear',
            'route:clear',
            'schedule:clear-cache',
            'view:clear',
            'clear-compiled',
        ];

        foreach ($artisanCommands as $command) {
            Log::info('running artisan command: '.$command);
            try {
                $this->info('running artisan command: '.$command, OutputInterface::VERBOSITY_VERBOSE);
                $this->callSilent($command);
            } catch (\Exception $e) {
                Log::error('error running artisan command: '.$command);
                Log::error($e->getMessage());
            }
        }

        $log = base_path('/storage/logs/laravel.log');
        file_put_contents($log, '');
        $this->info(filesize($log).' bytes cleared from laravel.log');
    }
}
