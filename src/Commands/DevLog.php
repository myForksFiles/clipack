<?php

namespace MyForksFiles\CliPack\Commands;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Process\Process;

class DevLog extends Command
{
    protected $signature = 'mff:dev:log
                            {protect? : Protect/Lock System}
                            {unlock?  : Unlock System}
                            {message? : Message}
                            {--a|all  : Show all}';

    protected $description = 'Lock/unlock system, store and show status log.';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['dev:log'];

    protected string $file = 'dev-status.log';

    protected string $message = '';

    protected string $dateTimeFormat = 'Y-m-d H:i:s.u';

    protected int $limit = 10;

    protected int $messageLength = 30;

    protected string $suffix = '_LOCK';

    /**
     * @var array<int, string>
     */
    protected array $lockFiles = [
        '.git',
        'composer.json',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    protected array $commands = [
        'git' => ['git', 'branch', '--show-current'],
        'who' => ['whoami'],
    ];

    public function __construct(
        protected LoggerInterface $logger,
        protected Filesystem $fileHandler,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        if ($this->getLaravel()->environment(['production'])) {
            throw new Exception('Not allowed on production.');
        }

        $this->file = storage_path($this->file);

        if (! $this->fileHandler->exists($this->file)) {
            $this->warn('File does not exist. Creating a new one.');
            $this->fileHandler->put($this->file, $this->message('init log'));
        }

        $action = $this->checkArguments();

        switch ($action) {
            case 'protect':
            case 'lock':
                if ($this->isLocked()) {
                    throw new Exception('System already locked.');
                }
                $this->systemLock(true);
                break;
            case 'unlock':
                if ($this->isUnLocked()) {
                    throw new Exception('System already unlocked.');
                }
                $this->systemLock(false);
                break;
            case 'message':
                $this->saveMessage($this->message($this->message));
                break;
            case '':
            default:
                $this->checkSystemStatus();
                $this->showLast();
        }

        return self::SUCCESS;
    }

    protected function checkSystemStatus(): ?string
    {
        if ($this->isLocked()) {
            return 'LOCKED!!!';
        }

        if ($this->isUnLocked()) {
            return 'unlocked';
        }

        $this->comment(PHP_EOL.'>>> System errors'.PHP_EOL);

        return null;
    }

    public function isLocked(): int
    {
        return $this->checkFiles(true);
    }

    public function isUnLocked(): int
    {
        return $this->checkFiles();
    }

    public function checkFiles(bool $locked = false): int
    {
        $results = 0;

        foreach ($this->lockFiles as $lockFile) {
            $file = $lockFile.($locked ? $this->suffix : '');
            if ($this->fileHandler->exists($file)) {
                $results++;
            }
        }

        return $results;
    }

    protected function systemLock(bool $status = false): void
    {
        $files = [];
        $message = 'Unlocking system ...';

        foreach ($this->lockFiles as $lockFile) {
            $source = $lockFile.$this->suffix;
            $target = $lockFile;

            if ($status) {
                $message = 'Setting protection/LOCK.';
                $source = $lockFile;
                $target = $lockFile.$this->suffix;
            }

            $files[$lockFile] = [
                'source' => $source,
                'target' => $target,
            ];
        }

        if ($this->message !== '') {
            $message .= ' - '.$this->message;
        }

        if ($status) {
            $results = $this->message($message);
            $this->lockFiles($files);
        } else {
            $this->lockFiles($files);
            $results = $this->message($message);
        }

        $this->saveMessage($results);
    }

    /**
     * @param  array<string, array{source: string, target: string}>  $files
     */
    protected function lockFiles(array $files): void
    {
        foreach ($files as $file) {
            if (! $this->fileHandler->exists($file['source'])) {
                throw new FileNotFoundException('File not found: '.$file['source']);
            }

            $this->fileHandler->move($file['source'], $file['target']);
            $this->logger->notice('SystemLock file: '.$file['source'].' > '.$file['target']);
        }
    }

    protected function checkArguments(): mixed
    {
        $results = $this->argument();
        unset($results['command']);
        reset($results);

        $action = current($results);
        if (! empty($action)) {
            $this->comment(PHP_EOL.'SystemStatus - ACTION: '.$action);
        }

        $message = next($results);
        if (! empty($message)) {
            $this->message = (string) $message;
        }

        return $action;
    }

    protected function showLast(): void
    {
        $this->info('Last entries');
        $headers = ['when', 'who', 'branch', 'message'];

        $contents = $this->fileHandler->get($this->file);
        $results = explode(PHP_EOL, $contents);
        $results = array_filter($results, static fn (string $row): bool => $row !== '');

        if ($results === []) {
            $this->comment('Empty file');

            return;
        }

        krsort($results);

        if (! $this->option('all')) {
            $results = array_slice($results, 0, $this->limit);
        }

        foreach ($results as &$result) {
            $result = explode(';', $result);
            if (isset($result[3]) && strlen($result[3]) > $this->messageLength) {
                $result[3] = implode(PHP_EOL, str_split($result[3], $this->messageLength));
            }
        }

        $this->table($headers, $results);
    }

    protected function message(string $message): string
    {
        return $this->getDate().';'
            .$this->getWho().';'
            .$this->getBranch().';'
            .$message
            .PHP_EOL;
    }

    protected function saveMessage(string $message): void
    {
        try {
            $this->fileHandler->append($this->file, $message);
            $this->line("<info>Message saved in log:</info> <comment>$message</comment>");
            $this->logger->notice('SystemLock: '.$message);
        } catch (FileException $e) {
            $this->error('Cannot save in log file: '.$e->getMessage());
            $this->logger->error('SystemLock - cannot save in log file: '.$message.$e->getMessage());
        }
    }

    protected function getBranch(): string
    {
        return $this->callCli('git');
    }

    protected function getWho(): string
    {
        return $this->callCli('who');
    }

    /**
     * @throws Exception
     */
    protected function callCli(string $what): string
    {
        if (! isset($this->commands[$what])) {
            throw new Exception('Call not allowed command '.$what);
        }

        $process = new Process($this->commands[$what]);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('Call command error: '.$process->getErrorOutput());
        }

        $results = trim($process->getOutput());

        if ($results === '' || $results === '0') {
            return 'unidentified '.$what;
        }

        return $results;
    }

    protected function getDate(): string
    {
        return CarbonImmutable::createFromTimestamp(microtime(true))->format($this->dateTimeFormat);
    }
}
