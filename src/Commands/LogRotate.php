<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class LogRotate extends Command
{
    protected $signature = 'mff:logs:rotate
                            {--path= : Source logs directory (default: storage/app/importLogs)}
                            {--archive= : Archive directory (default: storage/app/importLogsArchives)}
                            {--days=60 : Delete archived files older than this many days}
                            {--create-missing : Create source directory when missing}';

    protected $description = 'Archive log files and delete archives older than the retention period';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['knx:qs:logs:rotate'];

    public function handle(): int
    {
        $logsPath = $this->stringOptionOrConfig('path', 'clipack.log_rotate.path', storage_path('app/importLogs'));
        $archivePath = $this->stringOptionOrConfig('archive', 'clipack.log_rotate.archive', storage_path('app/importLogsArchives'));
        $daysOption = $this->option('days');
        $days = max(1, (int) (($daysOption !== null && $daysOption !== '') ? $daysOption : (config('clipack.log_rotate.days') ?: 60)));

        if (! is_dir($logsPath)) {
            if ($this->option('create-missing') || (bool) config('clipack.log_rotate.create_missing', false)) {
                File::ensureDirectoryExists($logsPath);
                $this->comment('Created missing logs directory: '.$logsPath);
            } else {
                $this->error('Logs directory does not exist: '.$logsPath);

                return self::FAILURE;
            }
        }

        File::ensureDirectoryExists($archivePath);

        try {
            $moved = $this->moveLogFiles($logsPath, $archivePath);
            $removed = $this->removeOldLogFiles($archivePath, $days);
        } catch (Throwable $e) {
            $this->error('Log rotation failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Logs rotated successfully.');
        $this->line('Moved: '.$moved);
        $this->line('Deleted old archives: '.$removed);
        $this->line('Source: '.$logsPath);
        $this->line('Archive: '.$archivePath);

        return self::SUCCESS;
    }

    private function stringOptionOrConfig(string $option, string $configKey, string $default): string
    {
        $optionValue = $this->option($option);
        if (is_string($optionValue) && $optionValue !== '') {
            return $optionValue;
        }

        $configValue = config($configKey);
        if (is_string($configValue) && $configValue !== '') {
            return $configValue;
        }

        return $default;
    }

    private function moveLogFiles(string $logsPath, string $archivePath): int
    {
        $today = date('Y-m-d');
        $moved = 0;

        foreach (File::files($logsPath) as $file) {
            $target = $archivePath.DIRECTORY_SEPARATOR.$today.'__'.$file->getFilename();
            File::move($file->getPathname(), $target);
            $moved++;
        }

        return $moved;
    }

    private function removeOldLogFiles(string $archivePath, int $days): int
    {
        $threshold = strtotime(sprintf('-%d days', $days));
        $removed = 0;

        if ($threshold === false) {
            return 0;
        }

        foreach (File::files($archivePath) as $file) {
            $mtime = $file->getMTime();

            if ($mtime !== false && $mtime < $threshold) {
                File::delete($file->getPathname());
                $removed++;
            }
        }

        return $removed;
    }
}
