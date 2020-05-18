<?php

namespace MacsiDigital\API\Support\Relations;

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
        } elseif(array_key_exists(Str::studly($name), $owner->getAttributes())){
            $this->hydrate($owner->getAttributes()[Str::studly($name)]);
            unset($owner->{Str::studly($name)});
        }
        $this->owner = $owner;
        $this->name = $name;
        $this->field = $field;
    }

    protected function hydrate($data) 
    {
    	if($data != []){
    		$this->relation = $this->related->newFromBuilder($data);
    	} else {
    		$this->relation = $this->related->newInstance();
    	} 
    }

    public function getResults()
    {
    	return $this->relation;
    }

}