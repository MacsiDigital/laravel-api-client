<?php

namespace MacsiDigital\API\Exceptions;

use Exception;

class ValidationFailedException extends Exception
{
    public function __construct($error)
    {
        parent::__construct('You have validation errors:- '.implode(', ', $error->all()));
    }
}
