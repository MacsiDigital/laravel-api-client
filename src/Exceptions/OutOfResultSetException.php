<?php

namespace MacsiDigital\API\Exceptions;

class OutOfResultSetException extends Base
{
    public function __construct()
    {
        parent::__construct('You are trying to retrieve results outside of the available results.');
    }
}
