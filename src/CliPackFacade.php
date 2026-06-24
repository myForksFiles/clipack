<?php

namespace MyForksFiles\CliPack;

use Illuminate\Support\Facades\Facade;

/**
 * Class CliPackFacade
 */
class CliPackFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'clipack';
    }
}
