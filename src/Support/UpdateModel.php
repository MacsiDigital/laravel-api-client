<?php

namespace MacsiDigital\API\Support;

use MacsiDigital\API\Traits\HasAttributes;
use MacsiDigital\API\Exceptions\ValidationFailedException;

class UpdateModel
{

    use HasAttributes;

    protected $validationAttributes = [];

}