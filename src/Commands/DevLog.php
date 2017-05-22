<?php
namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Filesystem\Filesystem as File;
use Exception;

/**
 *
 */
class DevLog extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'dev:log
                            {message?   : Message}
                            {--a|all    : Show all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store and show statusLog (storage/dev.log). Message as argument which should be saved, dont forget about "")';
    protected $file = 'dev.log';
    protected $fileHandler;
    protected $logger;
    protected $dateTime;
    protected $dateTimeFormat = 'Y-m-d H:i:s.u';
    protected $who = '';
    protected $branch = '';
    protected $limit = 10;
    protected $messageLength = 30;
    protected $message = '';

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
        $this->logger      = $logger;
        $this->dateTime    = $dateTime;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function fire()
    {
//        $app = $this->getApplication();
        if (!in_array(
            $this->getLaravel()->environment(),
            ['local', 'staging', 'debug'])
        ) {
            throw new Exception('Not Allowed on PRODUCTIVE!!!');
            return;
        }

        $this->file = storage_path() . DIRECTORY_SEPARATOR . $this->file;

        if (!$this->fileHandler->exists($this->file)) {
            $this->error('File not exist creating new one!');
            try {
                $this->fileHandler->put($this->file, $this->message('init log'));
            } catch (Exception $e) {
                $this->error('Can\'t create new log file! ', $e->getMessage());
            }
        }

        if ($this->argument('message')) {
            $this->save($this->argument('message'));
            return;
        }

        $this->showLast();
    }

    /**
     *
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
            if (strlen($value[3]) > $this->messageLength) {
                $value[3] = str_split($value[3], $this->messageLength);
                $value[3] = implode(PHP_EOL, $value[3]);
            }
        }

        $this->table($headers, $results);
    }

    /**
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
     * @param $message
     */
    protected function save($message)
    {
        if ($this->fileHandler->append($this->file, $this->message($message))) {
            $this->comment('Message saved in log.');
        } else {
            $this->error('Can\'t save in log file!');
        }
    }

    /**
     * @return string
     */
    protected function getBranch()
    {
        if (empty($this->branch)) {
            $results = exec('git branch | grep \\*');
            $results = str_replace(PHP_EOL, '', $results);
            $results = str_replace('*', '', $results);
            $results = trim($results);
            if (empty($results)) {
                $results = 'unidentifiedGitBRANCH';
            }
            $this->branch = $results;
        }

        return $this->branch;
    }

    /**
     * @return string
     */
    protected function getWho()
    {
        if (empty($this->who)) {
            $results = trim(exec('whoami'));
            if (empty($results)) {
                $results = 'unidentifiedUser';
            }
            $this->who = $results;
        }

        return $this->who;
    }

    /**
     * @return string
     */
    protected function getDate()
    {
        return $this->dateTime
            ->createFromFormat('U.u', microtime(true))
            ->format($this->dateTimeFormat);
    }
}
