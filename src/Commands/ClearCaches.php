<?php

namespace App\Console\Commands;

// use App\Console\Commands\Dev\Cache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearCaches extends Command
{
    protected $signature = 'dev:clear';

    protected $description = 'dev tests';

    protected $laravelFolders = [
        '/framework/cache',
        '/framework/cache/data',
        '/framework/sessions',
        '/framework/testing',
        '/framework/views',
        '/framework',
    ];

    public function handle()
    {
        $this->comment('caches');
        $this->cacheSize();
        $this->newLine();

        $this->info('clearing caches');
        $this->cleanStorage();
        $this->cleanArtisan();
        $this->cleanApacheLogs();

        file_put_contents(storage_path('logs/laravel.log'), '');

        $this->newLine();
        $this->comment('caches after clear');
        $this->cacheSize();
        $this->newLine();
    }

    private function cacheSize()
    {
        foreach ($this->laravelFolders as $dir) {
            $this->info('size of '.$dir.': '.self::folderSize(storage_path($dir)));
        }
    }

    private function cleanCache()
    {
        Cache::flush();
    }

    private function cleanStorage()
    {
        foreach ($this->laravelFolders as $dir) {
            $currentDir = storage_path($dir);
            if (! is_dir($currentDir)) {
                $this->info('path not found: '.$currentDir);
                if (! mkdir($currentDir, 0777, true) && ! is_dir($currentDir)) {
                    echo 'Failed to create directories...';
                }
            }

            $this->info('clearing: '.$currentDir);

            file_put_contents($currentDir.'/.gitkeep', '');
        }
    }

    private function cleanArtisan()
    {
        $artisanAll = \Artisan::all();

        foreach ([
            'cache:clear',
            'config:cache',
            'config:clear',
            'optimize:clear',
            'route:clear',
            'schedule:clear-cache',
            'view:clear',
            'clear-compiled',
            'knx:check:flags',
        ] as $artisanCommand
        ) {
            if (isset($artisanAll[$artisanCommand])) {
                Log::info('running artisan command: '.$artisanCommand);

                $this->callSilent($artisanCommand);
            }
        }
    }

    private function cleanApacheLogs()
    {
        $apacheLogsDir = base_path().'/../.ddev/logs/';
        if (! is_dir($apacheLogsDir)) {
            $this->info('apache logs dir not found: '.$apacheLogsDir);

            return;
        }

        foreach (
            [
                'access.log',
                'errors.log',
                'other_vhosts_access.log',
                'php-error.log',
            ] as $logFile
        ) {
            $currentLog = $apacheLogsDir.$logFile;
            if (! file_exists($currentLog)) {
                $this->comment('log file not found: '.$currentLog);
            }

            file_put_contents($currentLog, '');
            @chmod($currentLog, 0666);

            file_put_contents(storage_path('logs/laravel.log'), '');
        }
    }

    private static function folderSize($dir)
    {
        $size = 0;
        foreach (glob(rtrim((string) $dir, '/').'/*', GLOB_NOSORT) as $file) {
            $size += is_file($file) ? filesize($file) : self::folderSize($file);
        }

        return $size;
    }

    private function getCache()
    {
        // Retrieve the storage instance and the filesystem
        $storage = Cache::getStore(); // This returns an instance of FileStore
        $filesystem = $storage->getFilesystem(); // This returns an instance of Filesystem

        // Get all files from the root directory
        $files = $filesystem->allFiles('');

        // Optionally, use unique keys to avoid duplicates
        $keys = array_unique($files);
    }
}
