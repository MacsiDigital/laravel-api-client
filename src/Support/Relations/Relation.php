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

	public function __construct($related, $owner, $name, $field)
    {
        $this->relatedClass = $related;
        $this->related = new $related($owner->client);
        if(array_key_exists($name, $owner->getAttributes())){
            $this->hydrate($owner->getAttributes()[$name]);
            unset($owner->$name);
        } elseif(array_key_exists(Str::plural($name), $owner->getAttributes())){
        	$name = Str::plural($name);
			$this->hydrate($owner->getAttributes()[$name]);
            unset($owner->$name);
        } elseif(array_key_exists(Str::singular($name), $owner->getAttributes())){
        	$name = Str::singular($name);
			$this->hydrate($owner->getAttributes()[$name]);
            unset($owner->$name);
        }
        $this->owner = $owner;
        $this->name = $name;
        $this->field = $field;
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

    public function fill(array $data)
    {
    	return $this->related->fresh()->fill($data);
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
        return $this->related->fresh()->fill($data);
    }

    public function fresh()
    {
        return $this->related->fresh();
    }

    public function save($data)
    {
        
    }

    public function create($data)
    {
        return $this->related->fresh()->fill($data)->save();
    }

    public function get()
    {
    	return $this->getResults();
    }

    public function getResults()
    {
    	return $this->relation;
    }

    public function __call($method, $parameters) 
    {
        return $this->forwardCallTo($this->relation, $method, $parameters);
    }

}