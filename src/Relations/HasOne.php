<?php

namespace MacsiDigital\API\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class HasOne extends Relation
{
    protected $relation;

    public function __construct($related, $owner, $name, $field)
    {
        $this->relatedClass = $related;
        $this->related = new $related($owner->client);
        if(array_key_exists($name, $owner->getAttributes())){
            $this->hydrate($owner->getAttributes()[$name]);
            unset($owner->$name);
        }
        $this->owner = $owner;
        $this->name = $name;
        $this->field = $field;
    }

    protected function hydrate($data) 
    {
    	if($data != []){
    		$this->relation = $this->fill($data);
    	} else {
    		$this->relation = $this->related->fresh();
    	} 
    }

    public function fill(array $data)
    {
    	return $this->related->fresh()->fill($data);
    }

    public function getResults()
    {
    	return $this->relation;
    }

}