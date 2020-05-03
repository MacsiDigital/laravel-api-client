<?php

namespace MacsiDigital\API\Dev;

use MacsiDigital\API\Support\UpdateResource;

class UpdateUser extends UpdateResource
{
    protected $storeAttributes = [
    	'name' => 'string|max:255',
    	'email' => 'email|string|max:255',
    	'password' => 'string|max:10',
    ];
}
