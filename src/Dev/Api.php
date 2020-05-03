<?php

namespace MacsiDigital\API\Dev;

use Illuminate\Support\Facades\Http;
use MacsiDigital\API\Facades\Client;
use MacsiDigital\API\Support\Entry;
use MacsiDigital\API\Support\Authentication\NoAuthentication;

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
