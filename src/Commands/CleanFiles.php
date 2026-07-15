<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Delete / truncate cache, session, view and log files on disk.
 */
class CleanFiles extends Command
{
    protected $signature = 'mff:clean:files
                            {--skip-logs : Do not truncate log files}
                            {--extra-logs-dir= : Optional extra log directory to truncate}
                            {--report : Show directory sizes before and after}';

    protected $description = 'Delete Laravel storage cache/session/view files and truncate logs';

    /**
     * @var array<int, string>
     */
    protected $aliases = [
        'mff:files:clear',
    ];

    /**
     * Relative paths under storage/ whose contents should be wiped.
     *
     * @var array<int, string>
     */
    protected array $storageDirs = [
        'framework/cache',
        'framework/cache/data',
        'framework/sessions',
        'framework/testing',
        'framework/views',
    ];

    /**
     * @var array<int, string>
     */
    protected array $extraLogFiles = [
        'access.log',
        'errors.log',
        'error.log',
        'other_vhosts_access.log',
        'php-error.log',
    ];

    public function handle(): int
    {
        if ($this->option('report')) {
            $this->comment('Sizes before clean');
            $this->reportSizes();
            $this->newLine();
        }

        $failed = 0;

        try {
            $this->wipeStorageDirs();
        } catch (Throwable $e) {
            $failed++;
            $this->error('Failed wiping storage dirs: '.$e->getMessage());
        }

        if (! $this->option('skip-logs')) {
            try {
                $this->truncateLaravelLog();
                $this->truncateExtraLogs();
            } catch (Throwable $e) {
                $failed++;
                $this->error('Failed truncating logs: '.$e->getMessage());
            }
        }

        if ($this->option('report')) {
            $this->newLine();
            $this->comment('Sizes after clean');
            $this->reportSizes();
        }

        if ($failed > 0) {
            return self::FAILURE;
        }

        $this->info('File clean completed.');

        return self::SUCCESS;
    }

    private function wipeStorageDirs(): void
    {
        foreach ($this->storageDirs as $relative) {
            $path = storage_path($relative);

            if (! is_dir($path)) {
                File::ensureDirectoryExists($path);
                File::put($path.DIRECTORY_SEPARATOR.'.gitkeep', '');
                $this->line('Created: '.$path, null, OutputInterface::VERBOSITY_VERBOSE);

                continue;
            }

            $removed = 0;

            foreach (File::allFiles($path) as $file) {
                if ($file->getFilename() === '.gitkeep') {
                    continue;
                }

                File::delete($file->getPathname());
                $removed++;
            }

            foreach (File::directories($path) as $directory) {
                File::deleteDirectory($directory);
                $removed++;
            }

            $gitkeep = $path.DIRECTORY_SEPARATOR.'.gitkeep';
            if (! File::exists($gitkeep)) {
                File::put($gitkeep, '');
            }

            $this->info(sprintf('Wiped %s (%d items)', $relative, $removed));
        }
    }

    private function truncateLaravelLog(): void
    {
        $log = storage_path('logs/laravel.log');
        File::ensureDirectoryExists(dirname($log));
        $previousSize = File::exists($log) ? File::size($log) : 0;
        File::put($log, '');
        $this->info(sprintf('Truncated logs/laravel.log (%d bytes)', $previousSize));
    }

    private function truncateExtraLogs(): void
    {
        $extraDir = (string) ($this->option('extra-logs-dir') ?: config('clipack.clear_caches.extra_logs_dir', ''));

        if ($extraDir === '') {
            return;
        }

        if (! is_dir($extraDir)) {
            $this->comment('Extra logs dir not found: '.$extraDir);

            return;
        }

        foreach ($this->extraLogFiles as $logFile) {
            $currentLog = rtrim($extraDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$logFile;

            if (! File::exists($currentLog)) {
                $this->line('Log file not found: '.$currentLog, null, OutputInterface::VERBOSITY_VERBOSE);

                continue;
            }

            File::put($currentLog, '');
            @chmod($currentLog, 0666);
            $this->info('Truncated: '.$currentLog);
        }
    }

    private function reportSizes(): void
    {
        foreach ($this->storageDirs as $relative) {
            $path = storage_path($relative);
            $this->line(sprintf('size of %s: %d bytes', $relative, $this->folderSize($path)));
        }

        $laravelLog = storage_path('logs/laravel.log');
        $logSize = File::exists($laravelLog) ? File::size($laravelLog) : 0;
        $this->line(sprintf('size of logs/laravel.log: %d bytes', $logSize));
    }

    private function folderSize(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }

        $size = 0;

        foreach (File::allFiles($dir) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
