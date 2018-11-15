<?php

namespace MyForksFiles\CliPack;

use Illuminate\Support\Facades\Facade;

/**
 * Class CliPackFacade
 * @package MyForksFiles\CliPack
 *
 *- -***
 */
class CliPackFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'clipack';
    }
}
