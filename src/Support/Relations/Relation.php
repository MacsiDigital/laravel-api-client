<?php

namespace MacsiDigital\API\Support\Relations;

use MacsiDigital\API\Contracts\Relation as RelationContract;
use MacsiDigital\API\Traits\ForwardsCalls;

abstract class Relation implements RelationContract
{
    use ForwardsCalls;

    protected $owner;
    protected $related;
    protected $relatedClass;
    protected $name;
    protected $relation;
    protected $updateFields;

    public function newRelation($data = [])
    {
        return $this->related->newInstance($data);
    }

    public function getParentModelName()
    {
        $segments = explode('\\', get_class($this->parent));

        return strtolower(end($segments));
    }
 
    public function getRelatedModelName()
    {
        $segments = explode('\\', get_class($this->related));

        return strtolower(end($segments));
    }

    public function get()
    {
        return $this->getResults();
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->newRelation(), $method, $parameters);
    }
}
