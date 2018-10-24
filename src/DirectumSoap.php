<?php

namespace Kins\DirectumConnector;

use Illuminate\Support\Facades\Facade;

class DirectumSoap extends Facade
{
    protected static function getFacadeAccessor() { return 'DirectumService'; }
}
