<?php

namespace MacsiDigital\API\Support\Relations;

use Illuminate\Support\Str;
use MacsiDigital\API\Exceptions\NotAPersistableModel;
use MacsiDigital\API\Exceptions\RelationAlreadyExistsException;
use MacsiDigital\API\Traits\InteractsWithAPI;

class HasOne extends Relation
{
    protected $relation;

    public $type = 'HasOne';

    public function __construct($related, $owner, $name, $field, $updateFields = [])
    {
        $this->relatedClass = $related;
        $this->related = new $related($owner->client);
        $this->owner = $owner;
        $this->name = $name;
        $this->field = $field;
        $this->boot();
    }

    public function boot()
    {
        if (array_key_exists($this->name, $this->owner->getAttributes())) {
            $this->hydrate($this->owner->getAttributes()[$this->name]);
            unset($this->owner->{$this->name});
        } elseif (array_key_exists(Str::camel($this->name), $this->owner->getAttributes())) {
            $this->hydrate($this->owner->getAttributes()[Str::camel($this->name)]);
            unset($this->owner->{Str::camel($this->name)});
        } elseif (array_key_exists(Str::studly($this->name), $this->owner->getAttributes())) {
            $this->hydrate($this->owner->getAttributes()[Str::studly($this->name)]);
            unset($this->owner->{Str::studly($this->name)});
        } elseif (array_key_exists(Str::snake($this->name), $this->owner->getAttributes())) {
            $this->hydrate($this->owner->getAttributes()[Str::snake($this->name)]);
            unset($this->owner->{Str::snake($this->name)});
        }
    }

    protected function hydrate($data)
    {
        if ($data != []) {
            if ($this->owner->hasKey()) {
                $data[$this->field] = $this->owner->getKey();
            }
            $this->relation = $this->related->newFromBuilder($data);
        } else {
            $this->relation = $this->related->newInstance();
        }
    }

    public function empty()
    {
        $this->relation = null;

        return $this;
    }

    public function save(object $object)
    {
        if ($this->relation == null) {
            if ($object instanceof InteractsWithAPI) {
                if ($object instanceof $this->relatedClass) {
                    if ($this->owner->hasKey()) {
                        $object->{$this->field} = $this->owner->getKey();
                    }
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

    public function getResults()
    {
        if (empty($this->relation)) {
            $this->getRelationFromApi();
        }

        return $this->relation;
    }

    public function getRelationFromApi()
    {
        $this->relation = $this->newRelation([$this->field => $this->owner->getKey()]);
        if ($this->field != null && $this->owner->hasKey() && $this->relation != null) {
            $this->relation->{$this->field} = $this->owner->getKey();
        }

        return $this;
    }

    public function create($data)
    {
        return $this->save($this->make($data));
    }

    public function make($data)
    {
        return $this->relation = $this->related->newInstance()->fill($data);
    }

    // Be good to add these:- findOrNew, updateOrCreate
}
