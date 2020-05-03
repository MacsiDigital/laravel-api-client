<?php

namespace MacsiDigital\API\Exceptions;

class HttpException extends Base
{
	public function __construct($code = 0, $errorResponse = [], \Throwable $previous = null)
    {
        $message = $errorResponse['message'];
        parent::__construct($message, $code, $previous);
    }
}