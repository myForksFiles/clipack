<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Class ScheduleList
 *
 * @package MyForksFiles\CliPack\Commands
 * @author myForksFiles(at)gmail.com
 * @category CLI Laravel show schedule list
 *
 *- -***
 */
class ScheduleList extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'dev:scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List scheduled commands.';

    /**
     * Schedule
     *
     * @var Schedule
     */
    protected $schedule;

    /**
     * ScheduleList constructor.
     *
     * @param Schedule $schedule
     */
    public function __construct(Schedule $schedule)
    {
        parent::__construct();

        $this->schedule = $schedule;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $events = array_map(
            function ($event) {
                return [
                    'cron'        => $event->expression,
                    'description' => $event->description,
                    'command'     => static::getCommand($event->command),
                ];
            },
            $this->schedule->events()
        );

        $this->table(
            ['Cron', 'Description', 'Command', ],
            $events
        );
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
