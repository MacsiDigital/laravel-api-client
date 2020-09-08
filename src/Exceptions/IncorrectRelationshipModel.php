<?php

namespace MacsiDigital\API\Exceptions;

class IncorrectRelationshipModel extends Base
{
    public function __construct($related, $object)
    {
        parent::__construct('The relation must be of class '.get_class($related).', '.get_class($object).' passed.');
    }
}
