<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DbDumper extends Command
{
    protected $signature = 'mff:db:dump';

    protected $description = 'Create a MySQL database dump in storage/sqlDumps.';

    private string $dir = '';

    public function handle(): int
    {
        $credentials = $this->getCredentials();
        $targetPath = $this->getDumpTargetPath($credentials);

        $process = new Process([
            'mysqldump',
            '-u',
            $credentials['user'],
            '--password='.$credentials['password'],
            $credentials['db'],
        ]);
        $process->setTimeout(300);

        try {
            $process->mustRun();
            File::put($targetPath, $process->getOutput());
        } catch (ProcessFailedException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Database dump created: '.$targetPath);

        return self::SUCCESS;
    }

    /**
     * @return array{db: string, user: string, password: string}
     */
    private function getCredentials(): array
    {
        return [
            'db' => (string) config('database.connections.mysql.database'),
            'user' => (string) config('database.connections.mysql.username'),
            'password' => (string) config('database.connections.mysql.password'),
        ];
    }

    /**
     * @param  array{db: string, user: string, password: string}  $credentials
     */
    private function getDumpTargetPath(array $credentials): string
    {
        $this->dir = storage_path('sqlDumps');
        File::ensureDirectoryExists($this->dir);

        return $this->dir.DIRECTORY_SEPARATOR.date('Ymd-His').'_'.$credentials['db'].'_dbDump.sql';
    }
}
