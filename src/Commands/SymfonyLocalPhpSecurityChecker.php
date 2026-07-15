<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SymfonyLocalPhpSecurityChecker extends Command
{
    protected $signature = 'mff:security:check';

    protected $description = 'Run local-php-security-checker against composer.lock';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['mmf:security:check'];

    public function handle(): int
    {
        $binary = 'local-php-security-checker';

        if (! $this->commandExists($binary)) {
            $this->error("{$binary} is not installed or not available in PATH.");

            return self::FAILURE;
        }

        $lockFile = base_path('composer.lock');

        if (! is_file($lockFile)) {
            $this->error('composer.lock was not found in the project root.');

            return self::FAILURE;
        }

        $process = new Process([$binary, $lockFile]);
        $process->setTimeout(120);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            $message = 'Security check failed: '.$exception->getMessage();
            Log::error($message);
            $this->error($message);

            if ($process->getOutput() !== '') {
                $this->line(trim($process->getOutput()));
            }

            if ($process->getErrorOutput() !== '') {
                $this->line(trim($process->getErrorOutput()));
            }

            return self::FAILURE;
        }

        $output = trim($process->getOutput());
        Log::info('mff:security:check succeeded', ['output' => $output]);

        if ($output === '') {
            $this->info('No known security advisories found for installed packages.');
        } else {
            $this->line($output);
        }

        return self::SUCCESS;
    }

    private function commandExists(string $command): bool
    {
        $process = Process::fromShellCommandline('command -v '.escapeshellarg($command));
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }
}
