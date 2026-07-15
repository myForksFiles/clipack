<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;

class FreeSpace extends Command
{
    protected $signature = 'mff:disk:free
                            {--path=/ : Filesystem path to inspect}
                            {--limit=85 : Warn when used space percentage reaches this limit}';

    protected $description = 'Report free and used disk space';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['mff:space'];

    public function handle(): int
    {
        $path = (string) $this->option('path');
        $limit = (int) $this->option('limit');

        if (! is_dir($path) && $path !== '/') {
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
        $freePercent = ($free / $total) * 100;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Path', $path],
                ['Total', $this->formatBytes((int) $total)],
                ['Used', $this->formatBytes((int) $used).sprintf(' (%.2f%%)', $usedPercent)],
                ['Free', $this->formatBytes((int) $free).sprintf(' (%.2f%%)', $freePercent)],
                ['Warn limit', $limit.'% used'],
            ]
        );

        if ($usedPercent >= $limit) {
            $this->warn(sprintf('Disk usage is above the configured limit (%.2f%% >= %d%%).', $usedPercent, $limit));

            return self::FAILURE;
        }

        $this->info('Disk usage is within the configured limit.');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return sprintf('%.2f %s', $bytes / (1024 ** $power), $units[$power]);
    }
}
