<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class DevLog
 *
 * @package MyForksFiles\CliPack\Commands
 * @author myForksFiles(at)gmail.com
 * @category CLI Laravel simple cli dev log
 *
 *- -***
 */
class DevLog extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'dev:log
                            {protect? : Protect/Lock System}
                            {unlock?  : Unlock System}
                            {message? : Message}
                            {--a|all  : Show all}';

    /**
     * The console command description.
     * Message as argument which should be saved, don't forget about ""
     *
     * @var string
     */
    protected $description = 'Lock/unlock system, Store and show statusLog.';

    protected $file = 'dev-status.log';
    protected $message = '';
    protected $fileHandler;
    protected $logger;
    protected $dateTime;
    protected $dateTimeFormat = 'Y-m-d H:i:s.u';
    protected $who = '';
    protected $branch = '';
    protected $limit = 10;
    protected $messageLength = 30;

    /**
     * Append to file/dir name.
     *
     * @var string
     */
    protected $suffix = '_LOCK';

    /**
     * "Protected/locked" files/dirs.
     *
     * @var array
     */
    protected $lockFiles = [
        '.git',
        'composer.json',
    ];

    /**
     * Allowed CLI commands.
     *
     * @var array
     */
    protected $commands = [
        'git' => 'git branch | grep \\*',
        'who' => 'whoami',
    ];

    /**
     * constructor, a new command instance.
     *
     * @param Log $logger
     */
    public function __construct(
        Log $logger,
        File $fileHandler,
        Carbon $dateTime
    ) {
        $this->fileHandler = $fileHandler;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
//        $app = $this->getApplication();
        if (!in_array(
            $this->getLaravel()->environment(),
            ['local', 'staging', 'debug']
        )
        ) {
            throw new Exception('Not Allowed on PRODUCTIVE!!!');
            return;
        }

        $this->file = storage_path() . DIRECTORY_SEPARATOR . $this->file;

        if (!$this->fileHandler->exists($this->file)) {
            $this->error('File not exist creating new one!');
            try {
                $this->fileHandler->put(
                    $this->file,
                    $this->message('init log')
                );
            } catch (Exception $e) {
                $this->error('Can\'t create new log file! ', $e->getMessage());
            }
        }

        $action = $this->checkArguments();

        switch ($action) {
            case 'protect':
            case 'lock':
                if ($this->isLocked()) {
                    throw new Exception('System already Locked');
                }
                $this->systemLock(true);
                break;
            case 'unlock':
                if ($this->isUnLocked()) {
                    throw new Exception('System already unLocked');
                }
                $this->systemLock(false);
                break;
            case 'message':
                $this->saveMessage($this->message($this->message));
                return;
            case '':
            default:
                $this->checkSystemStatus();
                $this->showLast();
        }
    }

    /**
     * Check system status.
     *
     * @return string|void
     */
    protected function checkSystemStatus()
    {
        $status = $this->isLocked();
        if ($status) {
            return 'LOCKED!!!';
        }

        $status = $this->isUnLocked();
        if ($status) {
            return ' unlocked';
        }

        $this->comment(PHP_EOL . '>>> System errors: ' . $status . PHP_EOL);
        return;
    }

    /**
     * @return int
     */
    public function isLocked()
    {
        return $this->checkFiles(true);
    }

    /**
     * @return int
     */
    public function isUnLocked()
    {
        return $this->checkFiles();
    }

    /**
     * @param bool $locked
     * @return int
     */
    public function checkFiles($locked = false)
    {
        $results = 0;
        foreach ($this->lockFiles as $value) {
            if ($locked) {
                $value .= $this->suffix;
            }
            if ($this->fileHandler->exists($value)) {
                ++$results;
            }
        }

        return $results;
    }

    /**
     * Rename .git directory, and composer.json to protect project against changes/updates.
     *
     * @param bool $status
     */
    protected function systemLock($status = false)
    {
        $files = [];
        $message = 'Unlocking system ...';
        foreach ($this->lockFiles as $value) {
            $source = $value . $this->suffix;
            $target = $value;

            if ($status) {
                $message = 'setting protection/LOCK!!!';
                $source = $value;
                $target = $value . $this->suffix;
            }

            $files[$value] = [
                'source' => $source,
                'target' => $target,
            ];
        }

        $message .= (empty($this->message)) ?: ' - ' . $this->message;

        if ($status) { // unlock
            $results = $this->message($message);
            $this->lockFiles($files);
        } else {
            $this->lockFiles($files);
            $results = $this->message($message);
        }

        $this->saveMessage($results);
    }

    /**
     * @param $files
     */
    protected function lockFiles(array $files)
    {
        foreach ($files as $value) {
            if (!$this->fileHandler->exists($value['source'])) {
                throw new FileNotFoundException('File not found: ' . $value['source']);
            }
            $this->fileHandler->move($value['source'], $value['target']);
            $this->logger->notice('SystemLock file: ' . $value['source'] . ' > ' . $value['target']);
        }
    }

    /**
     * Get and check command arguments.
     *
     * @return mixed first arg
     */
    protected function checkArguments()
    {
        $results = $this->argument();
        unset($results['command']);
        reset($results);

        $action = current($results);
        if (!empty($action)) {
            $this->comment(PHP_EOL . 'SystemStatus - ACTION: ' . $action);
        }

        $message = next($results);
        if (!empty($message)) {
            $this->message = $message;
        }

        return $action;
    }

    /**
     * Show last entries from log.
     */
    protected function showLast()
    {
        $this->info('Last entries');
        $headers = ['when', 'who', 'branch', 'message'];

        $results = $this->fileHandler->get($this->file);

        if (count($results) < 1) {
            $this->comment('Empty file');

            return;
        }

        $results = explode(PHP_EOL, $results);
        end($results);
        if (empty($results[key($results)])) {
            unset($results[key($results)]);
        }
        krsort($results);

        if (!$this->option('all')) {
            $results = array_chunk($results, $this->limit);
            $results = $results[0];
        }

        foreach ($results as &$value) {
            $value = explode(';', $value);
            if (isset($value[3]) && strlen($value[3]) > $this->messageLength) {
                $value[3] = str_split($value[3], $this->messageLength);
                $value[3] = implode(PHP_EOL, $value[3]);
            }
        }

        $this->table($headers, $results);
    }

    /**
     * Prepare entry for log.
     *
     * @param $message
     * @return string
     */
    protected function message($message)
    {
        return $this->getDate() . ';'
            . $this->getWho() . ';'
            . $this->getBranch() . ';'
            . $message
            . PHP_EOL;
    }

    /**
     * Store entry in log file.
     *
     * @param string $message
     */
    protected function saveMessage($message)
    {
        try {
            $this->fileHandler->append($this->file, $message);
            $this->line("<info>Message saved in log:</info> <comment>$message</comment>");
            $this->logger->notice('SystemLock: ' . $message);
        } catch (FileException $e) {
            $this->error('Can\'t save in log file! ' . $e->getMessage());
            $this->logger->error('SystemLock - Can\'t save in log file!: ' . $message . $e->getMessage());
        }
    }

    /**
     * Execute CLI call and fetch results.
     *
     * @return mixed|string|void
     */
    protected function getBranch()
    {
        return $this->who = $this->callCli('git');
    }

    /**
     * @return string
     */
    protected function getWho()
    {
        return $this->who = $this->callCli('who');
    }

    /**
     * Call selected command.
     *
     * @param $what
     * @return mixed|string|void
     * @throws Exception
     */
    protected function callCli($what)
    {
        if (!isset($this->commands[$what])) {
            throw new Exception('Call not allowed command ' . (string)$what);
        }

        $process = new Process($this->commands[$what]);
        $process->run();

        if (!$process->isSuccessful()) {
//            $this->error('Call command error: ' . (string)$process->getErrorOutput());
            $this->logger->error('Call command error: ' . (string)$process->getErrorOutput());
        }

        $results = $process->getOutput();
        $results = str_replace(PHP_EOL, '', $results);
        $results = str_replace('*', '', $results);
        $results = trim($results);

        if (empty($results)) {
            $results = 'unidentified ' . $what;
        }

        return $results;
    }

    protected function getDate(): string
    {
        return $this->dateTime->createFromFormat('U.u', microtime(true))
            ->format($this->dateTimeFormat);
    }
}
