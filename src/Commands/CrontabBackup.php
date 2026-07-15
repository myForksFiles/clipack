<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CrontabBackup extends Command
{
    protected $signature = 'mff:crontab:backup
                            {--user= : System user whose crontab should be exported}
                            {--dir= : Output directory (default: storage/app/crontab)}';

    protected $description = 'Backup the current crontab to a timestamped file';

    public function handle(): int
    {
        $outputDir = (string) ($this->option('dir') ?: storage_path('app/crontab'));
        File::ensureDirectoryExists($outputDir);

        $target = $outputDir.DIRECTORY_SEPARATOR.date('Y-m-d_His').'_crontab.txt';
        $user = $this->option('user');

        $command = ['crontab', '-l'];
        if (is_string($user) && $user !== '') {
            $command = ['crontab', '-l', '-u', $user];
        }

        $process = new Process($command);
        $process->setTimeout(30);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            $this->error('Failed to read crontab: '.$exception->getMessage());

            if ($process->getErrorOutput() !== '') {
                $this->line(trim($process->getErrorOutput()));
            }

            return self::FAILURE;
        }

        File::put($target, $process->getOutput());
        $this->info('Crontab backup saved: '.$target);

        return self::SUCCESS;
    }
}
