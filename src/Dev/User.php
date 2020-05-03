<?php

namespace MacsiDigital\API\Dev;

use MacsiDigital\API\Support\Resource;

class User extends Resource
{

	protected $storeResource = 'MacsiDigital\Api\Dev\Resources\StoreUser';
    protected $updateResource = 'MacsiDigital\Api\Dev\Resources\UpdateUser';
    
}
