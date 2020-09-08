<?php

namespace MacsiDigital\API\Dev\Resources;

use MacsiDigital\API\Support\PersistResource;

class UpdateUser extends PersistResource
{
    protected $persistAttributes = [
        'name' => 'string|max:255',
        'email' => 'email|string|max:255',
        'password' => 'string|max:10',
        'address.street' => 'string|max:255',
        'address.town' => 'string|max:255',
        'address.postcode' => 'string|max:10',
    ];

    protected $relatedResource = [
    //	'address' => '\MacsiDigital\API\Dev\Resources\UpdateAddress'
    ];
}
