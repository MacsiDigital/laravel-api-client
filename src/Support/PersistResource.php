<?php

namespace MacsiDigital\API\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PersistResource
{
    protected $persistAttributes = [];
    protected $relatedResource = [];
    protected $mutateAttributes = [];

    protected $relations = [];
    protected $object;

    protected $attributes = [];

    public function fill(object $object, $type = "insert")
    {
        $this->object = $object;
        $this->{'fillFor'.ucfirst($type)}($object);
        foreach ($this->getRelatedResources() as $key => $rules) {
            if (! isset($this->attributes[$key])) {
                $this->attributes[$key] = [];
            }
        }
        $this->processEmpties();

        return $this;
    }

    protected function fillForInsert(object $object)
    {
        foreach ($object->getAttributes() as $key => $value) {
            if (Arr::exists($this->getPersistAttributes(), $key)) {
                $this->attributes[$key] = $value;
            } elseif (Arr::exists($this->getMutateAttributes(), $key)) {
                $this->processMutate($key, $value);
            } elseif (is_array($value)) {
                $this->attributes[$key] = $this->recursiveFill($key, $value);
            }
        }
        foreach ($object->getRelations() as $name => $relation) {
            if ($relation->type == 'HasOne') {
                $this->attributes[$name] = $this->processRelation($name, $relation->getResults());
            } elseif ($relation->type == 'HasMany') {
                $temp = [];
                foreach ($relation->getResults() as $object) {
                    $temp[] = $this->processRelation($name, $object);
                }
                $this->attributes[$name] = $temp;
            }
            $this->setRelation($name, $relation);
        }
    }

    protected function fillForUpdate(object $object)
    {
        if ($object->updatesOnlyDirty()) {
            $data = $object->getDirty();
        } else {
            $data = $object->getAttributes();
        }
        foreach ($data as $key => $value) {
            if (Arr::exists($this->getPersistAttributes(), $key)) {
                $this->attributes[$key] = $value;
            } elseif (Arr::exists($this->getMutateAttributes(), $key)) {
                $this->processMutate($key, $value);
            } elseif (is_array($value)) {
                $this->attributes[$key] = $this->recursiveFill($key, $value);
            }
        }
        foreach ($object->getRelations() as $name => $relation) {
            if ($relation->type == 'HasOne') {
                $this->attributes[$name] = $this->processRelation($name, $relation->getResults(), 'update');
            } elseif ($relation->type == 'HasMany') {
                $temp = [];
                foreach ($relation->getResults() as $object) {
                    $temp[] = $this->processRelation($name, $object, 'update');
                }
                $this->attributes[$name] = $temp;
            }
            $this->setRelation($name, $relation);
        }
    }

    public function processMutate($key, $value)
    {
        data_set($this->attributes, $this->getMutateKey($key), $value);
    }

    public function processManyFill($key, $value)
    {
        $temp = [];
        foreach ($value as $object) {
            $temp[] = $this->processRelation($key, $object);
        }
        $this->attributes[$key] = $temp;
    }

    public function processRelation($key, $object, $type = 'insert')
    {
        if (is_array($this->getRelatedClass($key))) {
            $attributes = $this->getRelatedClass($key);
            $temp = [];
            $array = $type == 'insert' || ! $object->updatesOnlyDirty() ? $object->getAttributes() : $object->getDirty();
            foreach ($array as $subKey => $subValue) {
                if (Arr::exists($attributes, $subKey)) {
                    $temp[$subKey] = $subValue;
                }
            }

            return $temp;
        } else {
            $resource = $this->getRelatedClass($key);
            $resource = new $resource();
            $resource->fill($object, $type);

            return $resource->getAttributes();
        }
    }

    public function recursiveFill($key, $value)
    {
        $array = [];
        foreach ($value as $childKey => $childValue) {
            if (Arr::exists($this->getPersistAttributes(), $key.'.'.$childKey)) {
                $array[$childKey] = $childValue;
            } elseif (is_array($childValue)) {
                $array[$childKey] = $this->recursiveFill($key.'.'.$childKey, $childValue);
            }
        }

        return $array;
    }

    public function getValidationAttributes()
    {
        $attributes = $this->getPersistAttributes();
        foreach ($this->getRelatedResources() as $key => $resource) {
            if (! is_array($resource)) {
                $object = new $resource;
                $resource = $object->getValidationAttributes();
            }
            foreach ($resource as $field => $rules) {
                if ($this->hasRelation($key)) {
                    $relation = $this->getRelation($key)->type;
                } elseif (! is_null($this->object) && method_exists($this->object, $key)) {
                    $relation = $this->object->$key()->type;
                } elseif (! is_null($this->object) && method_exists($this->object, Str::studly($key))) {
                    $relation = $this->object->{Str::studly($key)}()->type;
                } elseif (! is_null($this->object) && method_exists($this->object, lcfirst($key))) {
                    $relation = $this->object->{lcfirst($key)}()->type;
                } else {
                    $relation = 'HasOne';
                }
                if ($relation == 'HasOne') {
                    $attributes[$key] = 'array';
                    $attributes[$key.'.'.$field] = $rules;
                } elseif ($relation == 'HasMany') {
                    $attributes[$key] = 'array';
                    $attributes[$key.'.*.'.$field] = $rules;
                }
            }
        }

        return $attributes;
    }

    public function processEmpties()
    {
        foreach ($this->getAttributes() as $key => $value) {
            if (is_array($value)) {
                $this->attributes[$key] = $this->processRecursiveEmpty($value);
                if (empty($this->attributes[$key])) {
                    unset($this->attributes[$key]);
                }
            }
        }
    }

    public function processRecursiveEmpty(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->processRecursiveEmpty($value);
                if (empty($value)) {
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }

    public function getPersistAttributes()
    {
        return $this->persistAttributes;
    }

    public function getMutateAttributes()
    {
        return $this->mutateAttributes;
    }

    public function getMutateKey($key)
    {
        return $this->mutateAttributes[$key];
    }

    public function getRelatedResources()
    {
        return $this->relatedResource;
    }

    public function getRelatedClass($key)
    {
        return $this->relatedResource[$key];
    }

    public function setRelation($name, $relation)
    {
        $this->relations[$name] = $relation;
    }

    public function hasRelation($name)
    {
        return isset($this->relations[$name]);
    }

    public function getRelation($name)
    {
        return $this->relations[$name];
    }

    public function getAttributes($wrapped = '', $emptyArray = false)
    {
        if ($emptyArray) {
            $return = [$this->attributes];
        } else {
            $return = $this->attributes;
        }

        if ($wrapped == '') {
            return $return;
        }

        return [$wrapped => $return];
    }

    public function validate()
    {
        return Validator::make($this->getAttributes(), $this->getValidationAttributes());
    }

    public function updateRelatedResource()
    {
    }
}
