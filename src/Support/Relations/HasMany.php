<?php

namespace MacsiDigital\API\Support\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class HasMany extends Relation
{
    protected $relation;

    public $type = 'HasMany';

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
        } else {
            $this->relation = new Collection;
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

    public function make($data)
    {
        $object = $this->newRelation($data);
        if($object instanceof InteractsWithAPI){
            $object->{$this->field} = $this->owner->id;
        }
        $this->relation->push($object);
        return $object;
    }

    public function save(object $object)
    {
        if($object instanceof InteractsWithAPI){
            if($object instanceof $this->relatedClass){
                $object->{$this->field} = $this->owner->id;
                $object->save();
                $this->relation->push($object);
                return $object;
            } else {
                throw new IncorrectRelationshipModel($this->related, $object);
            }
        } else {
            throw new NotAPersistableModel($object);
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
        return $this->make($data)->save();
    }

    public function createMany(array $data)
    {
        foreach($data as $key => $value){
            $this->create($value);
        }
        return $this;
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