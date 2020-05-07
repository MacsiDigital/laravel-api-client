<?php

namespace MacsiDigital\API\Dev;

use MacsiDigital\API\Support\ApiResource;

class User extends ApiResource
{
	protected $storeResource = 'MacsiDigital\API\Dev\Resources\StoreUser';
    protected $updateResource = 'MacsiDigital\API\Dev\Resources\UpdateUser';

    public function addresses() 
    {
    	return $this->hasMany(Address::class);
    }

}
