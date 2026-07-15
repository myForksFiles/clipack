<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Clear Laravel application state via Artisan / framework APIs only.
 */
class CleanAll extends Command
{
    protected $signature = 'mff:clear
                            {--rebuild-config : Run config:cache after clearing}
                            {--no-flush : Do not call Cache::flush()}';

    protected $description = 'Clear Laravel caches using Artisan commands and Cache facade';

    /**
     * Legacy names kept for backward compatibility.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'clean',
        'cleanup',
        'mff:cleanup',
        'mff:clear:all',
        'mff:clean:up',
        'mff:cache:clear',
        'mff:cached',
        'mff:dev:clear',
        'dev:clear',
    ];

    /**
     * @var array<int, string>
     */
    protected array $artisanCommands = [
        'cache:clear',
        'config:clear',
        'route:clear',
        'view:clear',
        'optimize:clear',
        'schedule:clear-cache',
        'clear-compiled',
    ];

    public function handle(): int
    {
        $failed = 0;
        $registered = Artisan::all();

        $this->info('Clearing Laravel caches via Artisan...');

        foreach ($this->artisanCommands as $command) {
            if (! isset($registered[$command])) {
                $this->comment('Skipping missing command: '.$command, OutputInterface::VERBOSITY_VERBOSE);

                continue;
            }

            try {
                $this->line('Running: '.$command, null, OutputInterface::VERBOSITY_VERBOSE);
                Log::info('mff:clear running artisan command', ['command' => $command]);
                $this->callSilent($command);
                $this->info('OK: '.$command);
            } catch (Throwable $e) {
                $failed++;
                Log::error('mff:clear artisan command failed', [
                    'command' => $command,
                    'exception' => $e->getMessage(),
                ]);
                $this->error('Failed: '.$command.' ('.$e->getMessage().')');
            }
        }

        if (! $this->option('no-flush')) {
            Cache::flush();
            $this->info('OK: Cache::flush()');
        }

        if ($this->option('rebuild-config') && isset($registered['config:cache'])) {
            try {
                $this->callSilent('config:cache');
                $this->info('OK: config:cache');
            } catch (Throwable $e) {
                $failed++;
                $this->error('Failed: config:cache ('.$e->getMessage().')');
            }
        }

        if ($failed > 0) {
            $this->warn($failed.' step(s) failed.');

            return self::FAILURE;
        }

        $this->info('Laravel clear completed.');

        return self::SUCCESS;
    }
}
