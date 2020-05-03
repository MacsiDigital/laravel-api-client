<?php

namespace MacsiDigital\API\Support;

use MacsiDigital\API\Traits\HasAttributes;
use MacsiDigital\API\Exceptions\ValidationFailedException;

class UpdateResource
{
    use HasAttributes;

    protected $validationAttributes = [];

}