<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CleanAllCaches extends Command
{
    protected $signature = 'mff:cached';

    protected $description = 'Clean all Laravel caches and remove all cache files and entries';

    public function handle()
    {
        $this->info('Clearing all caches...');

        // Clear application cache
        Artisan::call('cache:clear');
        $this->info('Application cache cleared');

        // Clear route cache
        Artisan::call('route:clear');
        $this->info('Route cache cleared');

        // Clear config cache
        Artisan::call('config:clear');
        $this->info('Config cache cleared');

        // Clear view cache
        Artisan::call('view:clear');
        $this->info('View cache cleared');

        // Clear compiled files
        Artisan::call('clear-compiled');
        $this->info('Compiled files cleared');

        // Clear cache files from storage
        $cachePath = storage_path('framework/cache');
        if (File::isDirectory($cachePath)) {
            File::deleteDirectory($cachePath, true);
            File::makeDirectory($cachePath, 0777, true, true);
            $this->info('Cache files removed from storage');
        }

        // Clear cache entries
        Cache::flush();
        $this->info('Cache entries removed');

        $this->info('All caches cleaned successfully');
    }
}
