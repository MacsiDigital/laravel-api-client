<?php

namespace MacsiDigital\Api\Exceptions;

use Exception;

class RequestException extends Exception
{
    public $response;

    public function __construct(Response $response)
    {
        parent::__construct("HTTP request returned status code {$response->status()}.", $response->status());

        $this->response = $response;
    }
}