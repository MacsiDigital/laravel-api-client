<?php

namespace MacsiDigital\API\Dev;

use MacsiDigital\API\Support\Resource;

class Address extends Resource
{
    protected $storeResource = 'MacsiDigital\API\Dev\Resources\StoreAddress';
    protected $updateResource = 'MacsiDigital\API\Dev\Resources\UpdateAddress';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
