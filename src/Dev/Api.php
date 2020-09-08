<?php

namespace MacsiDigital\API\Dev;

use MacsiDigital\API\Facades\Client;
use MacsiDigital\API\Support\Entry;

class Api extends Entry
{
    protected $modelNamespace = 'MacsiDigital\API\Dev\\';

    public function newRequest()
    {
        $this->setConfig();
        
        return Client::baseUrl(config('api.base_url'))->withOptions(config('api.options'));
    }

    public function setConfig()
    {
        // Normally you would use a proper config file but for our tests we will include it
        $config = include 'config.php';
        config($config);
    }
}
