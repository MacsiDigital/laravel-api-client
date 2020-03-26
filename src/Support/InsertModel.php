<?php

namespace MacsiDigital\API\Support;

use MacsiDigital\API\Traits\HasAttributes;
use MacsiDigital\API\Exceptions\ValidationFailedException;

class InsertModel
{

    use HasAttributes;

    protected $validationAttributes = [];

}