<?php

namespace App\Console\Commands\Qs;

use App\Console\AbstractCommand;
use App\Notifications\ImportEmailNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\OutputInterface;

class DiskSpace extends AbstractCommand
{
    protected $signature = 'knx:qs:disk';

    protected $description = 'Disk space check';

    protected $isDeBug = false;

    public static $diskLog = 'checkDiskSpace.log';

    public static $checkResults = [];

    protected static $checkOptions = [
        'drive' => '/',
        'url' => '/qs/disk',
    ];

    public function handle()
    {
        $this->checkDiskSpace();

        return Command::SUCCESS;
    }

    //    public function scheduleCommand(Schedule $schedule): void
    //    {
    //
    //    }

    public function checkDiskSpace()
    {
        $status = self::check();

        $importEmailNotification = $this->setNotification(['status' => $status]);
        if ($importEmailNotification->priority < 4) {
            Notification::send($importEmailNotification->getDefaultUser(), $importEmailNotification);
        }

        if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
            $this->isDeBug = true;
            $this->newLine(5);
            $this->info('run: '.$this->description);
            $this->info(' >> '.self::$checkResults['summary']);
            unset(self::$checkResults['summary']);
            $this->table(array_keys(self::$checkResults), [array_values(self::$checkResults)]);
            $this->newLine(5);
        }
    }

    public static function check()
    {
        $result = self::diskSpaceCheck();

        $summary = sprintf(
            '%s free, %s (%.2f%%) used from %s',
            $result['free'],
            $result['used'],
            $result['percent'],
            $result['total']
        );
        $result['summary'] = $summary;

        self::$checkResults = $result;

        Storage::disk('local')->append(self::$diskLog, date('Y-m-d H:i:s').' - '.$summary);

        if (self::getUsageLimit() < $result['total']) {
            return true; // send notification via cron kernel
        }

        return false;
    }

    private static function calculateResult(int $value): string
    {
        return round($value / 1024 ** $i = floor(log($value, 1024)), 2)
            .' '
            .['b', 'kB', 'MB', 'GB', 'TB', 'PB'][$i];
    }

    private static function diskSpaceCheck(): array
    {
        $diskFree = disk_free_space(self::$checkOptions['drive']);
        $diskTotal = disk_total_space(self::$checkOptions['drive']);
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = $diskUsed / $diskTotal * 100;

        $diskFree = self::calculateResult($diskFree);
        $diskUsed = self::calculateResult($diskUsed);
        $diskTotal = self::calculateResult($diskTotal);

        return [
            'percent' => sprintf('%.2f', $diskPercent),
            'free' => $diskFree,
            'used' => $diskUsed,
            'total' => $diskTotal,
        ];
    }

    private static function getUsageLimit(): int
    {
        return config('knx.KNX_DISK_USAGE_LIMIT', 85);
    }

    private function setNotification(array $params): ImportEmailNotification
    {
        $alert = '';
        $importEmailNotification = new ImportEmailNotification;
        if (self::$checkResults['percent'] > (self::getUsageLimit() - 10)) {
            $importEmailNotification->setPriority(3);
            $importEmailNotification->toLog($importEmailNotification->getDefaultUser());
            $alert = ' ! WARNING ';
        }
        if ($params['status']) {
            $importEmailNotification->setPriority(1);
            $alert = ' !!! CRITICAL !!!';
        }

        $importEmailNotification->setImportNotification([
            'subject' => 'Disk space check'.$alert,
            'body' => 'Disk: '.self::$checkResults['summary'],
            'link' => self::$checkOptions['url'],
            'debug' => $this->isDeBug,
            'log' => self::$checkResults,
        ]);

        return $importEmailNotification;
    }
}
