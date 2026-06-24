<?php

namespace App\Console\Commands;

use App\Notifications\ImportEmailNotification;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SymfonyLocalPhpSecurityChecker extends Command
{
    protected $signature = 'mmf:security:check';

    protected $description = 'local-php-security-checker';

    protected $isDeBug = false;

    public function handle()
    {
        $this->isDeBug = $this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG;

        if ($this->isDeBug) {
            $this->newLine(1);
            $this->info('run: '.$this->description);
        }

        if ($this->runSymfonySecurityCheck()) {
            return Command::SUCCESS;
        } elseif ($this->isDeBug) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function scheduleCommand(Schedule $schedule): void
    {
        $schedule->command(self::class, [])
            ->weekly()
            ->saturdays()
            ->at('1:27')
            ->withoutOverlapping(10)
            ->onFailure(function (): void {
                $this->setNotification(['status' => 'failure to start local-php-security-checker']);
            });
    }

    private function runSymfonySecurityCheck(): bool
    {
        $localPhpSecurityChecker = 'local-php-security-checker';

        $exists = shell_exec('which '.$localPhpSecurityChecker);
        if (in_array($exists, ['', '0', false], true) || $exists === null) {
            if ($this->isDeBug) {
                $this->error(
                    PHP_EOL.PHP_EOL.'Command failed: '.$localPhpSecurityChecker.' is not installed. '.PHP_EOL
                );
                $this->newLine();
            }

            return false;
        }

        $command = $localPhpSecurityChecker.' '.base_path().DIRECTORY_SEPARATOR.'composer.lock';
        $process = Process::fromShellCommandline($command);

        try {
            $process->mustRun();

            Log::info($msg = 'Command: '.$this->description.' - success '.$process->getOutput());
            if ($this->isDeBug) {
                $this->info($msg);
            }

        } catch (ProcessFailedException $exception) {
            Log::error($msg = 'Command failed: '.$exception->getMessage());
            $this->error($msg);
        }

        //        $importEmailNotification = $this->setNotification(['status' => $process->getOutput()]);
        //        Notification::send($importEmailNotification->getDefaultUser(), $importEmailNotification);

        return true;
    }

    private function setNotification(array $params): ImportEmailNotification
    {
        $alert = '';
        $importEmailNotification = new ImportEmailNotification;

        if (stristr((string) $params['status'], 'CRITICAL')) {
            $importEmailNotification->setPriority(1);
            $alert = ' !!! CRITICAL !!!';
        }

        //        $importEmailNotification->setImportNotification([
        //            'subject' => 'Disk space check' . $alert,
        //            'body' => 'Disk: ' . self::$checkResults['summary'],
        //            'link' => self::$checkOptions['url'],
        //            'debug' => $this->isDeBug,
        //            'log' => self::$checkResults,
        //        ]);

        return $importEmailNotification;
    }
}
