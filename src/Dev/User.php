<?php

namespace MacsiDigital\API\Dev;

use MacsiDigital\API\Support\Resource;

class User extends Resource
{
    protected $storeResource = 'MacsiDigital\API\Dev\Resources\StoreUser';
    protected $updateResource = 'MacsiDigital\API\Dev\Resources\UpdateUser';

    public function address()
    {
        return $this->hasOne(Address::class);
    }
}
