<?php
namespace MacsiDigital\API\Support;

use MacsiDigital\API\Contracts\Entry;
use MacsiDigital\API\Traits\BuildsQueries;
use MacsiDigital\API\Traits\InteractsWithAPI;

class ApiResource extends Resource
{
	use InteractsWithAPI, BuildsQueries;

    public $client;

    public function __construct(Entry $client)
    {
        $this->client = $client;
    }

    public function fresh() 
    {
        return new static($this->client);
    }

}