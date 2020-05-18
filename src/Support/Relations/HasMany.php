<?php

namespace MacsiDigital\API\Support\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class HasMany extends Relation
{
    protected $relation;

    public function __construct($related, $owner, $name, $field, array $data = [])
    {
        $this->relatedClass = $related;
        $this->related = new $this->relatedClass($owner->client);
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

    protected function hydrate($array) 
    {
    	$collection = new Collection;
        if($array != []){
            foreach($array as $data){
                $collection->push($this->related->newFromBuilder($data));
            }
        }
    	$this->relation = $collection;
    }

    public function getResults()
    {
    	return $this->relation;
    }

    public function first() 
    {
    	return $this->relation->first();
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
        if(method_exists($this->relation, $method)){
            return $this->forwardCallTo($this->relation, $method, $parameters);
        } else {
            return $this->forwardCallTo($this->newInstance(), $method, $parameters);
        }   
    }

}