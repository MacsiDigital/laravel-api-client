<?php

namespace MacsiDigital\API\Exceptions;

class KeyNotFoundException extends Base
{
    public function __construct($class)
    {
        parent::__construct('Priamry key for '.$class.' not set, so cant perform this action.');
    }
}
