<?php

namespace MacsiDigital\API\Dev;

use MacsiDigital\API\Support\StoreResource;

class StoreUser extends StoreResource
{
    protected $storeAttributes = [
    	'name' => 'required|string|max:255',
    	'email' => 'required|email|string|max:255',
    	'password' => 'required|string|max:10',
    ];
    
}
