<?php

namespace MacsiDigital\API\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class BelongsTo extends Relation
{
    protected $relation;

    public function __construct($related, $owner, $name, $field, array $data = [])
    {
        $this->related = new $related($owner->client);
        $this->owner = $owner;
        $this->name = $name;
        $this->field = $field;
        $this->hydrate($data);
    }

    protected function hydrate($data) 
    {
    	if($data != []){
    		$this->relation = $this->fill($data);
    	} else {
    		$this->relation = $this->related->fresh();
    	} 
    }

    public function getResults()
    {
    	if($this->relation->exists()){
    		return $this->relation;
    	} else if(isset($owner->$field) && $owner->$field != null) {
    		return $this->relation = $this->related->newQuery()->find($owner->$field);
    	} else {
    		return $this->relation;
    	}
    }

}