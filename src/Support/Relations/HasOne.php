<?php

namespace MacsiDigital\API\Support\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use MacsiDigital\API\Traits\InteractsWithAPI;
use MacsiDigital\API\Exceptions\NotAPersistableModel;
use MacsiDigital\API\Exceptions\RelationAlreadyExistsException;

class HasOne extends Relation
{
    protected $relation;

    public $type = 'HasOne';

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

    public function make($data)
    {
        if($this->relation == null){
            $this->relation = $this->newRelation($data);
            if($this->relation instanceof InteractsWithAPI){
                $this->relation->{$this->field} = $this->owner->id;
            }
            return $this->relation;
        }
        throw new RelationAlreadyExistsException($this->owner, $this->related);
    }

    public function save(object $object)
    {
        if($this->relation == null){
            if($object instanceof InteractsWithAPI){
                if($object instanceof $this->relatedClass){
                    $object->{$this->field} = $this->owner->id;
                    $this->relation = $object->save();
                } else {
                    throw new IncorrectRelationshipModel($this->related, $object);
                }
            } else {
                throw new NotAPersistableModel($this->owner, $this->related);        
            }
        }
        throw new RelationAlreadyExistsException($this->owner, $this->related);
    }

    public function create($data)
    {
        return $this->save($this->make($data));
    }

    // Be good to add these:- findOrNew, updateOrCreate

}