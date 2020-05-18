<?php

namespace MacsiDigital\API\Support\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use MacsiDigital\API\Traits\ForwardsCalls;
use MacsiDigital\API\Contracts\Relation as RelationContract;

abstract class Relation implements RelationContract
{
    use ForwardsCalls;

	protected $owner;
    protected $related;
	protected $relatedClass;
	protected $name;

    public function newRelation()
    {
        return new $this->related->newInstance();
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

    public function update($data)
    {
        if(is_object($data) && $data instanceof $this->relatedClass){
            $this->relation = $data;
        } elseif(is_array($data)) {
            $this->relation->fill($data);
        }
        
        return $this->relation;
    }

    public function make($data)
    {
        return $this->newInstance($data);
    }

    public function save($data)
    {
        
    }

    public function create($data)
    {
        return $this->related->newInstnace($data)->save();
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
        return $this->forwardCallTo($this->newInstance(), $method, $parameters);
    }

}