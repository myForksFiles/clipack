<?php
namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;

/**
 * Class SetAuthBasic
 * @package MyForksFiles\CliPack\Commands
 *
 *- -***
 */
class SetAuthBasic extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'dev:authbasic';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Set up http Auth Basic on/off.';

    /**
     * @var Schedule
     */
    protected $schedule;

    /**
     * ScheduleList constructor.
     *
     * @param Schedule $schedule
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        
    }
}