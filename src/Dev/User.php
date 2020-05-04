<?php

namespace MacsiDigital\API\Dev;

use MacsiDigital\API\Support\ApiResource;

class User extends ApiResource
{

	protected $storeResource = 'MacsiDigital\Api\Dev\Resources\StoreUser';
    protected $updateResource = 'MacsiDigital\Api\Dev\Resources\UpdateUser';
    
}
