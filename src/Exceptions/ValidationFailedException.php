<?php

namespace MacsiDigital\Api\Exceptions;

use Exception;

class RequestException extends Exception
{
    public $response;

    public function __construct($errors)
    {
        parent::__construct();
    }
}