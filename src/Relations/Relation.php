<?php

namespace MacsiDigital\API\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use MacsiDigital\API\Contracts\Relation as RelationContract;

abstract class Relation implements RelationContract
{
	protected $owner;
    protected $related;
	protected $relatedClass;
	protected $name;

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

}