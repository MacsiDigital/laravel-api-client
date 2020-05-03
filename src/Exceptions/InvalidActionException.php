<?php

namespace MacsiDigital\API\Exceptions;

class InvalidActionException extends Base
{
	protected $class;

	public function __construct($class, $action)
	{
		$this->class = $class;
		$this->action = $action;
	}


}