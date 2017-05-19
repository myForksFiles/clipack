<?php
namespace MyForksFiles\CliPack\Commands;

use Carbon\Carbon;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Console\Command;
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
                            {message?   : Message as argument which should be saved, dont forget about "")}
                            {--a|all    : Show all entries, default is 10 last.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store and show statusLog (storage/dev.log).';
    protected $file = 'dev.log';
    protected $fileHandler;
    protected $logger;
    protected $dateTime;
    protected $dateTimeFormat = 'Y-m-d H:i:s.u';
    protected $who = '';
    protected $branch = '';
    protected $limit = 10;
    protected $message = '';

    /**
     * constructor, a new command instance.
     *
     * @param Log $logger
     */
    public function __construct(Log $logger,
                                File $fileHandler,
                                Carbon $dateTime
    )
    {
        $this->fileHandler = $fileHandler;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
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
            $this->error('Filenot exist creating new one!');
            try {
                $this->fileHandler->put($this->file, $this->message('init log'));
            } catch (Exception $e) {
                $this->error('Can\'t create new log file! ' , $e->getMessage());
            }
        }

        if ($this->argument('message')) {
            $this->save($this->argument('message'));
            return;
        }

        $this->showLast();
    }

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
        }

        $this->table($headers, $results);
    }

    protected function message($message)
    {
        return $this->getDate() . ';'
            . $this->getWho() . ';'
            . $this->getBranch() . ';'
            . $message
            . PHP_EOL;
    }

    protected function save($message)
    {
        if ($this->fileHandler->append($this->file, $this->message($message))) {
            $this->comment('Message saved in log.');
        } else {
            $this->error('Can\'t save in log file!');
        }
    }

    protected function getBranch()
    {
        if (empty($this->branch)) {
            $results = exec('git branch | grep \\*');
            $results = str_replace('*', '', $results);
            $results = trim($results);
            if (empty($results)) {
                $results = 'unidentifiedGitBRANCH';
            }
            $this->branch = $results;
        }

        return $this->branch;
    }

    protected function getWho()
    {
        if (empty($this->who)) {
            $results = exec('whoami');
            if (empty($results)) {
                $results = 'unidentifiedUser';
            }
            $this->who = $results;
        }

        return $this->who;
    }

    protected function getDate()
    {
        return $this->dateTime
            ->createFromFormat('U.u', microtime(true))
            ->format($this->dateTimeFormat);
    }
}
