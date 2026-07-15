<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class RunPhp extends Command
{
    protected $signature = 'mff:runphp
                            {file : Path to file.}
                            {--c|class= : Class which should be called from file.}
                            {--force : Confirm execution of a local development helper file.}';

    protected $description = 'Include and execute a PHP helper file from an explicitly allowed local path.';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('This command is not allowed in production.');

            return self::FAILURE;
        }

        if (! (bool) config('clipack.run_php.enabled', false)) {
            $this->error('This command is disabled. Set CLIPACK_RUN_PHP_ENABLED=true to enable it.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->error('Refusing to execute without --force.');

            return self::FAILURE;
        }

        $file = $this->normalizePath((string) $this->argument('file'));
        $allowedPath = $this->normalizePath((string) config('clipack.run_php.allowed_path'));

        if (! str_starts_with($file, $allowedPath.DIRECTORY_SEPARATOR)) {
            $this->error('File is outside of the configured allowed path: '.$allowedPath);

            return self::FAILURE;
        }

        if (! $this->files->exists($file) || ! $this->files->isFile($file)) {
            $this->error('Wrong path or file does not exist.');

            return self::FAILURE;
        }

        $this->executeFile($file);

        return self::SUCCESS;
    }

    private function normalizePath(string $path): string
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            throw new RuntimeException('Path does not exist: '.$path);
        }

        return $realPath;
    }

    private function executeFile(string $file): void
    {
        $this->info('Executing file: '.$file);

        include $file;

        if ($this->option('class')) {
            $className = (string) $this->option('class');
            new $className;
        }
    }
}
