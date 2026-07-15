<?php

namespace MyForksFiles\CliPack;

use DateTimeImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Shared helpers for CliPack commands.
 */
trait CliPackTools
{
    public static function getFileAuthBasicProtection(): string
    {
        $authBasicFile = Config::get('clipack.file_auth_basic_protection');

        if (! is_string($authBasicFile) || $authBasicFile === '') {
            $authBasicFile = 'auth_basic_protection';
        }

        return storage_path($authBasicFile);
    }

    public static function checkAuthBasicStatus(): bool
    {
        if ((bool) config('clipack.auth_basic.enabled', false)) {
            return true;
        }

        return File::exists(self::getFileAuthBasicProtection());
    }

    /**
     * File size in human readable format.
     *
     * @see http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
     */
    public static function fileSize(int|string $bytes, int $decimals = 2, string $separator = ','): string
    {
        $bytes = (int) $bytes;
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = $bytes > 0 ? (int) floor((strlen((string) $bytes) - 1) / 3) : 0;
        $factor = min($factor, count($size) - 1);

        $results = sprintf(
            "%.{$decimals}f",
            $bytes / 1024 ** $factor
        );
        $results = str_replace('.', $separator, $results);

        return $results.' '.$size[$factor];
    }

    public static function getDate(string $date = '', string $format = ''): string
    {
        $format = $format === '' ? 'Y-m-d H:i:s' : $format;

        return (new DateTimeImmutable)->format($format);
    }

    public static function commandExist(string $cmd): bool
    {
        $process = Process::fromShellCommandline('command -v '.escapeshellarg($cmd));
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }

    /**
     * @param  array<int, string>  $command
     */
    public static function callCommand(array $command): string
    {
        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            return $process->getErrorOutput();
        }

        return $process->getOutput();
    }

    public function taskInfo(string $status, string $task): void
    {
        $this->info((new DateTimeImmutable)->format('Y-m-d H:i:s').' '.$status.' '.$task);
    }
}
