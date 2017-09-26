<?php
namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem as File;
use Exception;

/**
 * Class RunPhp
 * @package MyForksFiles\CliPack\Commands
 *
 *- -***
 */
class RunPhp extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'dev:runphp 
                            {file      : Path to file.}
                            {--c|class : Class which should be called from file.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Include and execute php file.';

    public function __construct(File $fileHandler)
    {
        $this->fileHandler = $fileHandler;
        parent::__construct();
    }

    public function fire()
    {
        if (!$this->argument('file')) {
            $this->info('Please specify file to call.');
        }

        $file = $this->argument('file');

        if (!$this->fileHandler->exists($file)) {
            $this->info('Wrong path or file not exist.');
        }

        $this->executeFile($file);
    }

    private function executeFile($file)
    {
        $this->info('Executing file: ' . $file);
        include $file;

        if ($this->option('class')) {
            $className = $this->option('class');
            $callClass = new $className();
        }

        return;
    }
}