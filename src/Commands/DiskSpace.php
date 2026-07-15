<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DiskSpace extends Command
{
    protected $signature = 'mff:disk:check
                            {--path=/ : Filesystem path to inspect}
                            {--limit=85 : Fail when used percentage reaches this limit}
                            {--log : Append a summary line to the package disk log file}';

    protected $description = 'Check disk free/used space and optionally write a log entry';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['knx:qs:disk'];

    public function handle(): int
    {
        $path = (string) $this->option('path');
        $limit = (int) $this->option('limit');

        if ($path !== '/' && ! is_dir($path)) {
            $this->error('Path does not exist: '.$path);

            return self::FAILURE;
        }

        $total = disk_total_space($path);
        $free = disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            $this->error('Unable to read disk space for path: '.$path);

            return self::FAILURE;
        }

        $used = $total - $free;
        $usedPercent = ($used / $total) * 100;
        $summary = sprintf(
            '%s free, %s (%.2f%%) used from %s on %s',
            $this->formatBytes((int) $free),
            $this->formatBytes((int) $used),
            $usedPercent,
            $this->formatBytes((int) $total),
            $path
        );

        $this->table(
            ['Metric', 'Value'],
            [
                ['Path', $path],
                ['Total', $this->formatBytes((int) $total)],
                ['Used', $this->formatBytes((int) $used).sprintf(' (%.2f%%)', $usedPercent)],
                ['Free', $this->formatBytes((int) $free)],
                ['Warn limit', $limit.'% used'],
                ['Summary', $summary],
            ]
        );

        if ($this->option('log')) {
            $logRelative = (string) config('clipack.disk.log_file', 'clipack-disk-space.log');
            $logPath = storage_path('logs/'.$logRelative);
            File::ensureDirectoryExists(dirname($logPath));
            File::append($logPath, now()->toDateTimeString().' - '.$summary.PHP_EOL);
            $this->line('Logged to: '.$logPath);
        }

        if ($usedPercent >= $limit) {
            $this->warn(sprintf('Disk usage is above the configured limit (%.2f%% >= %d%%).', $usedPercent, $limit));

            return self::FAILURE;
        }

        $this->info('Disk usage is within the configured limit.');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return sprintf('%.2f %s', $bytes / (1024 ** $power), $units[$power]);
    }
}
