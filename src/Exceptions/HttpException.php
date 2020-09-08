<?php

namespace MacsiDigital\API\Exceptions;

use Exception;

class HttpException extends Exception
{
    public function __construct($code = 0, $message, \Throwable $previous = null)
    {
        parent::__construct('HTTP Request returned Status Code '.$code.'. '.$message);
    }
}
