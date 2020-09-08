<?php

namespace MacsiDigital\API\Support\Relations;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use MacsiDigital\API\Exceptions\IncorrectRelationshipModel;

class HasMany extends Relation
{
    protected $relation;

    public $type = 'HasMany';

    public function __construct($related, $owner, $name, $field, $updateFields = [])
    {
        $this->relatedClass = $related;
        $this->related = new $this->relatedClass($owner->client);
        $this->owner = $owner;
        $this->name = $name;
        $this->field = $field;
        $this->setUpdateFields($updateFields);
        $this->boot();
    }

    protected function setUpdateFields($fields)
    {
        $this->updateFields = $fields;
        if ($this->owner->hasKey()) {
            $this->updateFields[$this->field] = $this->owner->getKey();
        }
    }

    public function hasUpdateFields()
    {
        return $this->updateFields != [];
    }

    public function getUpdateKeys()
    {
        return array_keys($this->updateFields);
    }

    public function updateFields($item)
    {
        if (is_array($item)) {
            foreach ($this->updateFields as $key => $value) {
                $item[$key] = $value;
            }
        } elseif (is_object($item)) {
            foreach ($this->updateFields as $key => $value) {
                $item->$key = $value;
            }
        }

        return $item;
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
        } else {
            $this->relation = new Collection;
        }
    }

    protected function hydrate($array)
    {
        $collection = new Collection;
        if ($array != []) {
            foreach ($array as $data) {
                $collection->push($this->related->newFromBuilder($this->updateFields($data)));
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
        $this->attach(($object = $this->newRelation($this->updateFields($data))));

        return $object;
    }

    public function attach($object)
    {
        $this->relation->push($this->updateFields($object));

        return $this;
    }

    public function detach($object)
    {
    }

    public function save(object $object)
    {
        if ($object instanceof $this->relatedClass) {
            $this->attach($this->updateFields($object)->save());

            return $object;
        } else {
            throw new IncorrectRelationshipModel($this->related, $object);
        }
    }

    public function saveMany(array $data)
    {
        foreach ($data as $key => $value) {
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
        foreach ($data as $key => $value) {
            $this->create($value);
        }

        return $this;
    }

    public function getResults()
    {
        if ($this->relation->count() == 0) {
            $this->getRelationFromApi();
        }

        return $this->relation;
    }

    public function first()
    {
        return $this->getResults()->first()->fresh();
    }

    public function last()
    {
        return $this->getResults()->last()->fresh();
    }

    public function getRelationFromApi()
    {
        if (method_exists($relation = $this->newRelation($this->updateFields([])), 'setPassOnAttributes')) {
            $this->relation = $relation->setPassOnAttributes($this->getUpdateKeys())->all();
        }
        if ($this->hasUpdateFields()) {
            foreach ($this->relation as $object) {
                $this->updateFields($object);
            }
        }

        return $this;
    }

    public function nextPage()
    {
        $this->relation = $this->relation->nextPage();
        if ($this->hasUpdateFields()) {
            foreach ($this->relation as $object) {
                $this->updateFields($object);
            }
        }
    }

    public function prevPage()
    {
        $this->relation = $this->relation->prevPage();
        if ($this->hasUpdateFields()) {
            foreach ($this->relation as $object) {
                $this->updateFields($object);
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
        if ($this->relation->count() > 0 && method_exists($this->relation, $method)) {
            return $this->forwardCallTo($this->relation, $method, $parameters);
        } else {
            $relation = $this->newRelation($this->updateFields([]));
            if (method_exists($relation, 'setPassOnAttributes')) {
                $relation->setPassOnAttributes($this->getUpdateKeys());
            }

            return $this->forwardCallTo($relation, $method, $parameters);
        }
    }

    // Be good to add these:- findOrNew, firstOrNew, firstOrCreate and updateOrCreate
}
