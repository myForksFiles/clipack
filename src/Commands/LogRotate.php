<?php

namespace App\Console\Commands\Qs;

use App\Console\Imports\AbstractImport;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class LogRotate extends Command
{
    protected $signature = 'knx:qs:logs:rotate';

    protected $description = 'Rotate daily import logs by archiving old logs and deleting them after a specified time';

    private $logsPath;

    private $archivePath;

    public function handle()
    {
        $this->setDirs();

        $this->moveLogFiles();
        $this->removeOldLogFiles();

        $this->info('Logs rotated successfully!');

        return Command::SUCCESS;
    }

    public function scheduleCommand(Schedule $schedule): void
    {
        $schedule->command(self::class, [])->daily()->at('0:12');
    }

    private function moveLogFiles()
    {
        $today = date('Y-m-d');

        // Get all files from the logs directory
        $files = array_diff(scandir($this->logsPath), ['.', '..']);
        foreach ($files as $file) { // Archive files older than a day
            rename($this->logsPath.'/'.$file, $this->archivePath.'/'.$today.'__'.$file);
        }
    }

    private function removeOldLogFiles()
    {
        // Delete archived files older than 60 says
        $archivedFiles = array_diff(scandir($this->archivePath), ['.', '..']);
        foreach ($archivedFiles as $archivedFile) {
            if (filemtime($this->archivePath.'/'.$archivedFile) < strtotime('-60 days')) {
                unlink($this->archivePath.'/'.$archivedFile);
            }
        }
    }

    private function setDirs()
    {
        $this->logsPath = storage_path('app/importLogs');
        $this->archivePath = storage_path('app/importLogsArchives');

        if (! is_dir($this->logsPath)) {
            $msg = 'Logs directory does not exist';
            Log::critical($msg);
            throw new Exception($msg);
        }

        // Create an archives directory if not exists
        if (! is_dir($this->archivePath)) {
            AbstractImport::makeDir($this->archivePath);
        }
    }
}
