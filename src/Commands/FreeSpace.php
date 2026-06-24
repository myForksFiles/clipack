<?php

namespace App\Console\Commands\Qs;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

class FreeSpace extends Command
{
    protected $signature = 'mff:space';

    protected $description = 'check disk space';

    protected static $space = 0;

    public function handle()
    {
        $this->info('run: '.self::class, OutputInterface::VERBOSITY_VERBOSE);

        $results = static::runDiskSpaceCheck();

        $this->info('space: '.self::$space, OutputInterface::VERBOSITY_VERBOSE);
        $this->info('results: '.(bool) $results, OutputInterface::VERBOSITY_VERBOSE);

        return Command::SUCCESS;
    }

    public static function runDiskSpaceCheck(int $limitPercent = 85)
    {
        $drive = '/';
        self::$space = (disk_free_space($drive) / disk_total_space($drive)) * 100;

        return $limitPercent < (int) self::$space;
    }
}
