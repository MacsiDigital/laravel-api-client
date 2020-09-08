<?php

namespace MacsiDigital\API\Dev\Resources;

use MacsiDigital\API\Support\PersistResource;

class StoreAddress extends PersistResource
{
    protected $persistAttributes = [
        'street' => 'required|string|max:255',
        'town' => 'required|string|max:255',
        'postcode' => 'required|string|max:10',
    ];
}
