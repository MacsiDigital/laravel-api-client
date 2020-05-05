<?php

namespace MacsiDigital\API\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class HasMany extends Relation
{
    protected $relation;

    public function __construct($related, $owner, $name, $field, array $data = [])
    {
        $this->related = new $related($owner->client);
        $this->hydrate($data, $owner->attributes[$name]);
        unset($owner->$name);
        $this->owner = $owner;
        $this->name = $name;
        $this->field = $field;
    }

    protected function hydrate($data, $default) 
    {
    	$collection = new Collection;
    	if($data != []){
    		foreach($data as $item){
    			$collection->push($this->fill($item));
    		}
    	} elseif($default != null){
    		foreach($default as $item){
    			$collection->push($this->fill($item));
    		}
    	}
    	$this->relation = $collection;
    }

    public function fill(array $data)
    {
    	return $this->related->fresh()->fill($data);
    }

    public function getResults()
    {
    	return $this->relation;
    }

    public function first() 
    {
    	return $this->relation->first();
    }

}