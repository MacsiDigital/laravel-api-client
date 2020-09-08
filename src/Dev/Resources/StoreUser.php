<?php

namespace MacsiDigital\API\Dev\Resources;

use MacsiDigital\API\Support\PersistResource;

class StoreUser extends PersistResource
{
    protected $persistAttributes = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|string|max:255',
        'password' => 'required|string|max:10',
        'address.street' => 'string|max:255',
        'address.town' => 'string|max:255',
        'address.postcode' => 'string|max:10',
    ];
}
