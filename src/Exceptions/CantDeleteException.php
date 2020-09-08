<?php

namespace MacsiDigital\API\Exceptions;

class CantDeleteException extends Base
{
    public function __construct($class, $key)
    {
        parent::__construct('There was an error deleting '.$class.' with a key of '.$key);
    }
}
