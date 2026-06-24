<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;

/**
 * Class DbDumper
 *
 * @author myForksFiles(at)gmail.com
 *
 * @category CLI Laravel clear tools
 *
 *- -***
 */
class ExportLang extends Command
{
    protected $signature = 'mff:lang:export';

    protected $description = 'mysqldump';

    public function handle() {}
}
