<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * Import a MySQL dump into the configured database.
 */
class DbImporter extends Command
{
    protected $signature = 'mff:db:import
        {file : Path to the SQL dump file}
        {--connection= : Database connection name from config/database.php}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Import a SQL dump into a MySQL database';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $connectionName = (string) ($this->option('connection') ?: Config::get('database.default'));

        if (! is_file($file) || ! is_readable($file)) {
            $this->error('SQL dump file not found or not readable: '.$file);

            return self::FAILURE;
        }

        $connection = Config::get("database.connections.{$connectionName}");

        if (! is_array($connection)) {
            $this->error('Database connection not found: '.$connectionName);

            return self::FAILURE;
        }

        if (($connection['driver'] ?? null) !== 'mysql') {
            $this->error('Only MySQL connections are supported by this command.');

            return self::FAILURE;
        }

        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? '');
        $password = (string) ($connection['password'] ?? '');
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');

        if ($database === '' || $username === '') {
            $this->error('The selected database connection is missing required credentials.');

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Connection', $connectionName);
        $this->components->twoColumnDetail('Database', $database);
        $this->components->twoColumnDetail('Host', $host.':'.$port);
        $this->components->twoColumnDetail('File', realpath($file) ?: $file);

        if (! $this->option('force') && ! $this->confirm('Import this SQL dump into the selected database?', false)) {
            $this->comment('Import cancelled.');

            return self::INVALID;
        }

        $command = $this->buildMysqlImportCommand(
            file: $file,
            host: $host,
            port: $port,
            database: $database,
            username: $username,
            password: $password,
        );

        try {
            $this->info('Starting import...');

            $result = $this->runImportCommand($command, ['MYSQL_PWD' => $password]);

            if ($result['exit_code'] !== 0) {
                $this->error('Import failed.');

                if ($result['stderr'] !== '') {
                    $this->line(trim($result['stderr']));
                }

                return self::FAILURE;
            }

            $this->info('Import completed successfully.');

            if ($result['stdout'] !== '') {
                $this->line(trim($result['stdout']));
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Import failed with exception: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function buildMysqlImportCommand(
        string $file,
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
    ): string {
        $mysqlBinary = $this->mysqlBinary();

        return sprintf(
            '%s --host=%s --port=%s --user=%s %s < %s',
            escapeshellcmd($mysqlBinary),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($file),
        );
    }

    /**
     * @return array{exit_code:int,stdout:string,stderr:string}
     */
    private function runImportCommand(string $command, array $environment = []): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, base_path(), array_merge($_ENV, $environment));

        if (! is_resource($process)) {
            throw new \RuntimeException('Could not start mysql import process.');
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]) ?: '';
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exit_code' => (int) $exitCode,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
        ];
    }

    private function mysqlBinary(): string
    {
        $configuredBinary = Config::get('clipack.mysql_binary');

        if (is_string($configuredBinary) && $configuredBinary !== '') {
            return $configuredBinary;
        }

        return 'mysql';
    }
}
