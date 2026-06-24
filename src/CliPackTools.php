<?php

namespace MyForksFiles\CliPack;

use Config;
use DateTime;
use File;
use Symfony\Component\Process\Process;

/**
 * Class CliPackTools
 */
trait CliPackTools
{
    /**
     * @return string
     */
    public static function getFileAuthBasicProtection()
    {
        $authBasicFile = Config::get('packages.MyForksFiles.CliPack.app.fileAuthBasicProtection');
        if (empty($authBasicFile)) {
            $authBasicFile = 'auth_basic_protection';
        }

        return storage_path($authBasicFile);
    }

    /**
     * @return bool
     */
    public static function checkAuthBasicStatus()
    {
        if (! empty(env('AUTH_USER')) && ! empty(env('AUTH_PW'))) {
            return true;
        }

        return (bool) File::exists(self::getFileAuthBasicProtection());
    }

    /**
     * File size in human readable format.
     *
     * @see http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
     *
     * @param  $bytes  int fileSize
     * @param  int  $decimals
     * @param  string  $separator
     * @return string
     */
    public static function fileSize($bytes, $decimals = 2, $separator = ',')
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        $results = sprintf(
            "%.{$decimals}f",
            $bytes / 1024 ** $factor
        );
        $results = str_replace('.', $separator, $results);

        return $results.(' '.$size[$factor] ?? 'B');
    }

    /**
     * @return string
     */
    public static function getDate($date = '', $format = '')
    {
        $format = (empty($format)) ? 'Y-m-d H:i:s' : $format;
        $results = DateTime::createFromFormat('U.u', microtime(true));

        return $results->format($format);
    }

    /**
     * @see https://stackoverflow.com/questions/12424787/how-to-check-if-a-shell-command-exists-from-php
     */
    public static function commandExist($cmd)
    {
        return ! (in_array(shell_exec("which $cmd"), ['', '0'], true) || shell_exec("which $cmd") === false || shell_exec("which $cmd") === null);
    }

    /**
     * Call shell command.
     *
     * @return string
     *
     * @see https://symfony.com/doc/current/components/process.html#disabling-output
     */
    public static function callCommand($command)
    {
        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            return $process->getErrorOutput();
        }

        return $process->getOutput();
    }

    /**
     * Get productive url
     *
     * @return string
     */
    private function getUrlApp()
    {
        $urlApp = Config::get('app.url');

        if (empty($urlApp)) {
            $this->error('App url NOT DEFINED command > command Canceled');
            exit;
        }

        return $urlApp;
    }

    /**
     * check current app status
     *
     * @return bool
     */
    private function checkStatus()
    {
        $status = false;

        if (! $this->checkEnv()) {
            $this->error(
                'This command is NOT allowed on current environment: '
                .$this->env
            );
            exit;
        }

        if ($this->checkUrl() && $this->getConfirmation()) {
            $status = true;
        }

        return $status;
    }

    /**
     * Show and log error.
     */
    public function handleError($msg)
    {
        $this->error($msg);
        $this->logger->error($msg);
    }

    /**
     * check env
     *
     * @return bool
     */
    private function checkEnv()
    {
        $status = false;

        if ($this->env != 'production'
            && in_array($this->env, $this->allowedEnv)
        ) {
            $status = true;
        }

        return $status;
    }

    /**
     * Simple helper for console output.
     */
    public function taskInfo($status, $task)
    {
        $this->info((new DateTime)->format('Y-m-d H:i:s').' '.$status.' '.$task);
        $this->logger->info($status.': '.$task);
    }
}
