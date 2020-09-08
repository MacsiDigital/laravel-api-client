<?php

namespace MacsiDigital\API\Exceptions;

class RelationAlreadyExistsException extends Base
{
    public function __construct($owner, $related)
    {
        parent::__construct(get_class($owner).' already has a set relationship from '.get_class($related).' this is a One relationship and cna only have 1 set relation.');
    }
}
