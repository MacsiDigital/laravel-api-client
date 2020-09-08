<?php

namespace MacsiDigital\API\Exceptions;

class NotAPersistableModel extends Base
{
    public function __construct($related)
    {
        parent::__construct(get_class($related).' cannot be saved directly to the API.  It needs to be saved as part of its parent.');
    }
}
