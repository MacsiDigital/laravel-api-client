<?php

namespace MacsiDigital\API\Traits;

use LogicException;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use MacsiDigital\API\Contracts\Relation;
use Illuminate\Database\Eloquent\Concerns\HasAttributes as LaravelHasAttributes;

trait HasAttributes
{
    use LaravelHasAttributes;

    public function make(array $attributes)
    {
        return $this->fill($attributes);
    }

    public function fill(array $attributes)
    {
        foreach($attributes as $key => $value){
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    public function getDates()
    {
        return $this->dates;
    }

     /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if(is_array($value) || is_object($value)){
            if($this->relationLoaded($key)){
                $this->updateRelationAttribute($key, $value);
                return $this;
            }
        }
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            $method = 'set'.Str::studly($key).'Attribute';

            return $this->{$method}($value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        elseif ($value && $this->isDateAttribute($key)) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isJsonCastable($key) && ! is_null($value)) {
            $value = $this->castAttributeAsJson($key, $value);
        }

        // If this attribute contains a JSON ->, we'll set the proper value in the
        // attribute's underlying array. This takes care of properly nesting an
        // attribute in the array's value in the case of deeply nested items.
        if (Str::contains($key, '->')) {
            return $this->fillJsonAttribute($key, $value);
        }

        $this->attributes[$key] = $value;

        if(is_array($value)){
            $this->setRelationAttribute($key, $value);
        }

        return $this;
    }

    protected function setRelationAttribute($key, $value) 
    {
        if($this->loadRaw || in_array($key, $this->dontAutoloadRelation)){
            return;
        }
        if ($this->isRelationship($key)){
            $function = $key;
        } elseif ($this->isRelationship(Str::plural($key))) {
            $function = Str::plural($key);
        }
        if(isset($function)){
            $this->$function();
        }
    }

    protected function updateRelationAttribute($key, $value) 
    {
        if($this->loadRaw || in_array($key, $this->dontAutoloadRelation)){
            return;
        }
        if ($this->isRelationship($key)){
            $this->getRelation($key)->update($value);
        } elseif ($this->isRelationship(Str::plural($key))) {
            $key = Str::plural($key);
            $this->getRelation($key)->update($value);
        }
    }

    public function getAttribute($key)
    {
        if (! $key) {
            return;
        }

        // If the attribute exists in the attribute array or has a "get" mutator we will
        // get the attribute's value. Otherwise, we will proceed as if the developers
        // are asking for a relationship's value. This covers both types of values.
        if (array_key_exists($key, $this->attributes) ||
            $this->hasGetMutator($key)) {
            return $this->getAttributeValue($key);
        }

        // Here we will determine if the model base class itself contains this given key
        // since we don't want to treat any of those methods as relationships because
        // they are all intended as helper methods and none of these are relations.
        if (method_exists(self::class, $key)) {
            return;
        }

        return $this->getRelationValue($key);
    }

    /**
     * Get a relationship.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if ($this->relationLoaded($key)) {
            return $this->getRelation($key)->getResults();
        }

        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * Get a relationship value from a method.
     *
     * @param  string  $method
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod($method)
    {
        $relation = $this->$method();

        if (! $relation instanceof Relation) {
            if (is_null($relation)) {
                throw new LogicException(sprintf(
                    '%s::%s must return a relationship instance, but "null" was returned. Was the "return" keyword used?', static::class, $method
                ));
            }

            throw new LogicException(sprintf(
                '%s::%s must return a relationship instance.', static::class, $method
            ));
        }

        $this->setRelation($method, $relation);

        $relation->getResults();
    }

    public function package() 
    {
        $class = new static($this->client);
        $class->loadRaw = true;
        $class->fill($this->getAttributes());
        foreach($this->relations as $key => $relation){
            $class->$key = $relation->getResults()->toArray();
        }
        return $class;
    }
}