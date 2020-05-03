<?php

namespace MacsiDigital\API\Facades;

use Illuminate\Support\Facades\Facade;

class Client extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'api.client';
    }
}
