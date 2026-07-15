<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleList extends Command
{
    protected $signature = 'mff:schedule:list';

    protected $description = 'List scheduled commands.';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['mff:scheduled'];

    public function __construct(protected Schedule $schedule)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $events = array_map(
            fn ($event) => [
                'cron' => $event->expression,
                'description' => $event->description,
                'command' => static::getCommand($event->command),
            ],
            $this->schedule->events()
        );

        $this->table(
            ['Cron', 'Description', 'Command'],
            $events
        );

        return self::SUCCESS;
    }

    protected static function getCommand(string $command): string
    {
        $parts = explode(' ', $command);

        if (count($parts) > 2 && $parts[1] === "'artisan'") {
            array_shift($parts);
        }

        return implode(' ', $parts);
    }
}
