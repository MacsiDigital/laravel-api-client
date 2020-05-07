<?php

namespace MacsiDigital\API\Exceptions;

class NoClientSetException extends Base
{
	public function __construct() 
	{
		parent::__construct('No client returned in your API Client.  You must create a newRequest() function in your API Entry point returning a Client');
	}
}