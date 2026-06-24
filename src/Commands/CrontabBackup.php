<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class CrontabBackup
 */
class CrontabBackup extends Command
{
    protected $signature = 'mff::crontab';

    protected $description = 'backup current crontab';

    protected $isDeBug = false;

    protected $script = 'crontab -l -u www > ./%s.txt';

    public function handle()
    {
        $this->isDeBug = $this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG;

        if ($this->isDeBug) {
            $this->newLine(1);
            $this->info('run: '.$this->description);
        }

        $process = Process::fromShellCommandline(sprintf($this->script, date('Y-m-d_His')));

        try {
            $process->mustRun();

            if ($this->isDeBug) {
                $this->info('results: '.$process->getOutput());
            }

            return true;
        } catch (ProcessFailedException $exception) {
            $this->error('Command failed: '.$exception->getMessage());

            return false;
        } finally {
        }

        return Command::SUCCESS;
    }

    public function scheduleCommand(Schedule $schedule): void
    {
        $schedule->command(self::class, [])
            ->weekly()
            ->saturdays()
            ->at('1:25');
    }
}
