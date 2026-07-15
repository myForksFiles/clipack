<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class CleanUp
 *
 * @author myForksFiles(at)gmail.com
 *
 * @category CLI Laravel clear tools
 *
 *- -***
 */
class ApacheLogs extends Command
{
    protected $signature = 'mff:logs:clear {apacheLogsDir?} {--type=apache}';

    protected $description = 'Clear Laravel and web server logs';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['mff:apache:logs'];

    private const string TYPE_APACHE = 'apache';

    private const string TYPE_NGINX = 'nginx';

    private const array APACHE_LOGS = [
        'access.log',
        'error.log',
        'other_vhosts_access.log',
        'php-error.log',
    ];

    private const array NGINX_LOGS = [
        'error.log',
        'access.log',
    ];

    protected string $logsDirApache = '/var/log/apache2/';

    protected string $logsDirNginx = '/var/log/nginx/';

    public function handle(): int
    {
        $type = strtolower((string) ($this->option('type') ?? self::TYPE_APACHE));

        if (! in_array($type, [self::TYPE_APACHE, self::TYPE_NGINX], true)) {
            $this->error('Unsupported log type: '.$type);

            return Command::FAILURE;
        }

        $this->info('Cleaning Laravel and web server logs', OutputInterface::VERBOSITY_VERBOSE);

        $status = [
            $this->cleanLaravelLog(),
            $type === self::TYPE_APACHE ? $this->cleanApacheLogs() : $this->cleanNginxLogs(),
        ];

        return in_array(Command::FAILURE, $status, true) ? Command::FAILURE : Command::SUCCESS;
    }

    private function cleanLaravelLog(): int
    {
        $laravelLog = storage_path('logs/laravel.log');

        if (! file_exists($laravelLog)) {
            $this->comment('Laravel log file not found: '.$laravelLog, OutputInterface::VERBOSITY_VERBOSE);

            return Command::FAILURE;
        }

        return $this->truncateLog($laravelLog)
            ? Command::SUCCESS
            : Command::FAILURE;
    }

    private function cleanNginxLogs(): int
    {
        return $this->clearLogsInDirectory($this->logsDirNginx, self::NGINX_LOGS);
    }

    private function cleanApacheLogs(): int
    {
        $apacheLogsDir = (string) ($this->argument('apacheLogsDir') ?? $this->logsDirApache);

        return $this->clearLogsInDirectory($apacheLogsDir, self::APACHE_LOGS);
    }

    private function clearLogsInDirectory(string $directory, array $logs): int
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (! is_dir($directory)) {
            $this->comment('Log directory not found: '.$directory, OutputInterface::VERBOSITY_VERBOSE);

            return Command::FAILURE;
        }

        $hasErrors = false;

        foreach ($logs as $log) {
            $currentLog = $directory.$log;

            if (! $this->truncateLog($currentLog)) {
                $hasErrors = true;
            }
        }

        return $hasErrors ? Command::FAILURE : Command::SUCCESS;
    }

    private function truncateLog(string $currentLog): bool
    {
        if (! file_exists($currentLog)) {
            $this->comment('Log file not found: '.$currentLog, OutputInterface::VERBOSITY_VERBOSE);

            return false;
        }

        try {
            file_put_contents($currentLog, '');
            @chmod($currentLog, 0666);

            return true;
        } catch (Throwable $e) {
            $this->error('Error clearing log file: '.$currentLog, OutputInterface::VERBOSITY_VERBOSE);
            $this->line($e->getMessage(), null, OutputInterface::VERBOSITY_VERBOSE);

            return false;
        }
    }
}
