<?php

namespace MacsiDigital\API\Support\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use MacsiDigital\API\Exceptions\NotAPersistableModel;
use MacsiDigital\API\Exceptions\IncorrectRelationshipModel;

class HasMany extends Relation
{
    protected $relation;

    public $type = 'HasMany';

    public function __construct($related, $owner, $name, $field)
    {
        $this->relatedClass = $related;
        $this->related = new $this->relatedClass($owner->client);
        $this->owner = $owner;
        $this->name = $name;
        $this->field = $field;
        $this->boot();
    }

    public function boot() 
    {
        if(array_key_exists($this->name, $this->owner->getAttributes())){
            $this->hydrate($this->owner->getAttributes()[$this->name]);
            unset($this->owner->{$this->name});
        } elseif(array_key_exists(Str::camel($this->name), $this->owner->getAttributes())){
            $this->hydrate($this->owner->getAttributes()[Str::camel($this->name)]);
            unset($this->owner->{Str::camel($this->name)});
        } elseif(array_key_exists(Str::studly($this->name), $this->owner->getAttributes())){
            $this->hydrate($this->owner->getAttributes()[Str::studly($this->name)]);
            unset($this->owner->{Str::studly($this->name)});
        } elseif(array_key_exists(Str::snake($this->name), $this->owner->getAttributes())){
            $this->hydrate($this->owner->getAttributes()[Str::snake($this->name)]);
            unset($this->owner->{Str::snake($this->name)});
        } else {
            $this->relation = new Collection;
        }
    }

    protected function hydrate($array) 
    {
    	$collection = new Collection;
        if($array != []){
            foreach($array as $data){
                if($this->owner->hasKey()){
                    $data[$this->field] = $this->owner->getKey();
                }
                $collection->push($this->related->newFromBuilder($data));
            }
        }
    	$this->relation = $collection;
    }

    public function empty() 
    {
        $this->relation = new Collection;
        return $this;
    }

    public function make($data)
    {
        $object = $this->newRelation($data);
        if($object instanceof InteractsWithAPI){
            if($this->owner->hasKey()){
                $object->{$this->field} = $this->owner->getKey();
            }
        }
        $this->attach($object);
        return $object;
    }

    public function attach($object)
    {
        $this->relation->push($object);
        return $this;
    }

    public function detach($object)
    {
        
    }

    public function save(object $object)
    {
        if($object instanceof $this->relatedClass){
            if($this->field != null && $this->owner->hasKey()){
                $object->{$this->field} = $this->owner->getKey();
            }
            $object->save();
            $this->attach($object);
            return $object;
        } else {
            throw new IncorrectRelationshipModel($this->related, $object);
        }
    }

    public function saveMany(array $data)
    {
        foreach($data as $key => $value){
            $this->save($value);
        }
        return $this;
    }

    public function create($data)
    {
        return $this->save($this->make($data));
    }

    public function createMany(array $data)
    {
        foreach($data as $key => $value){
            $this->create($value);
        }
        return $this;
    }

    public function getResults() 
    {
        if($this->relation->count() == 0){
            $this->getRelationFromApi();
        }   
        return $this->relation;
    }

    public function first() 
    {
        return $this->getResults()->first()->fresh();
    }


    public function getRelationFromApi() 
    {
        // Not throwing error on bad call - maybe need to create a rawData method on Builder
        $this->relation = $this->related->newInstance([$this->field => $this->owner->getKey()])->all();
        if($this->field != null && $this->owner->hasKey()){
            foreach($this->relation as $object){
                $object->{$this->field} = $this->owner->getKey();
            }
        }
        return $this;
    }

    public function nextPage() 
    {
        $this->relation = $this->relation->nextPage();
        if($this->field != null && $this->owner->hasKey()){
            foreach($this->relation as $object){
                $object->{$this->field} = $this->owner->getKey();
            }
        }
    }

    public function prevPage() 
    {
        $this->relation = $this->relation->prevPage();
        if($this->field != null && $this->owner->hasKey()){
            foreach($this->relation as $object){
                $object->{$this->field} = $this->owner->getKey();
            }
        }
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

    // Be good to add these:- findOrNew, firstOrNew, firstOrCreate and updateOrCreate

}