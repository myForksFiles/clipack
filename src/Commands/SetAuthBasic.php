<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use MyForksFiles\CliPack\CliPackTools;

class SetAuthBasic extends Command
{
    use CliPackTools;

    protected $signature = 'mff:auth:basic
                            {action? : on|off|status}
                            {--status : Show current basic auth protection status}';

    protected $description = 'Toggle or inspect HTTP Basic Auth protection flag file';

    public function handle(): int
    {
        $action = strtolower((string) ($this->argument('action') ?? ''));

        if ($this->option('status') || $action === 'status' || $action === '') {
            return $this->showStatus();
        }

        return match ($action) {
            'on', 'enable', '1', 'true' => $this->enable(),
            'off', 'disable', '0', 'false' => $this->disable(),
            default => $this->invalidAction($action),
        };
    }

    private function showStatus(): int
    {
        $enabled = self::checkAuthBasicStatus();
        $path = self::getFileAuthBasicProtection();

        $this->line('Basic auth protection: '.($enabled ? 'ON' : 'OFF'));
        $this->line('Flag file: '.$path);
        $this->line('Flag file exists: '.(File::exists($path) ? 'yes' : 'no'));

        return self::SUCCESS;
    }

    private function enable(): int
    {
        $path = self::getFileAuthBasicProtection();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, now()->toDateTimeString().PHP_EOL);

        $this->info('Basic auth protection enabled.');
        $this->line('Flag file: '.$path);

        return self::SUCCESS;
    }

    private function disable(): int
    {
        $path = self::getFileAuthBasicProtection();

        if (File::exists($path)) {
            File::delete($path);
        }

        $this->info('Basic auth protection disabled.');
        $this->line('Flag file: '.$path);

        return self::SUCCESS;
    }

    private function invalidAction(string $action): int
    {
        $this->error('Invalid action: '.$action);
        $this->line('Usage: php artisan mff:auth:basic {on|off|status}');

        return self::INVALID;
    }
}
