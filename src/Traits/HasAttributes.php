<?php

namespace MacsiDigital\API\Traits;

use Illuminate\Database\Eloquent\Concerns\HasAttributes as LaravelHasAttributes;
use Illuminate\Support\Str;
use LogicException;
use MacsiDigital\API\Contracts\Relation;

trait HasAttributes
{
    use LaravelHasAttributes;

    protected $updateOnlyDirty = true;

    public function hasKey()
    {
        return false;
    }

    public function updatesOnlyDirty()
    {
        return $this->updateOnlyDirty;
    }

    public function make(array $attributes)
    {
        return $this->fill($attributes);
    }

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
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
        if (is_array($value) || is_object($value)) {
            if (method_exists($this, 'setRelation')) {
                if ($this->relationLoaded($key)) {
                    $this->updateRelationAttribute($key, $value);

                    return $this;
                }
            }
        }

        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        elseif ($value && $this->isDateAttribute($key)) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isClassCastable($key)) {
            $this->setClassCastableAttribute($key, $value);

            return $this;
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

        if (is_array($value) && method_exists($this, 'setRelation')) {
            $this->setRelationAttribute($key, $value);
        }

        return $this;
    }

    protected function setRelationAttribute($key, $value)
    {
        if ($this->loadRaw || in_array($key, $this->dontAutoloadRelation)) {
            return;
        }
        if (static::$snakeAttributes) {
            $tempKey = Str::camel($key);
        } else {
            $tempKey = $key;
        }
        if ($this->isRelationship($tempKey)) {
            $function = $tempKey;
        } elseif ($this->isRelationship(Str::plural($tempKey))) {
            $function = Str::plural($tempKey);
        }
        if (isset($function)) {
            $this->$function();
        }
    }

    protected function updateRelationAttribute($key, $value)
    {
        if ($this->loadRaw || in_array($key, $this->dontAutoloadRelation)) {
            return;
        }

        if ($this->isRelationship($key)) {
            $this->getRelation($key)->update($value);
        } elseif ($this->isRelationship(Str::plural($key))) {
            $key = Str::plural($key);
            $this->getRelation($key)->update($value);
        } elseif ($this->isRelationship(Str::singular($key))) {
            $key = Str::singular($key);
            $this->getRelation($key)->update($value);
        }
    }

    public function getAttribute($key)
    {
        if (! $key) {
            return;
        }

        // need to check for a relationship first - otherwise we will be passing back the
        // array if relationship is passed as attributes in the API call
        if (method_exists($this, 'setRelation') && $this->isRelationship($key)) {
            return $this->getRelationValue($key);
        }

        // If the attribute exists in the attribute array or has a "get" mutator we will
        // get the attribute's value. Otherwise, we will proceed as if the developers
        // are asking for a relationship's value. This covers both types of values.
        if (array_key_exists($key, $this->attributes) ||
            array_key_exists($key, $this->casts) ||
            $this->hasGetMutator($key) ||
            $this->isClassCastable($key)) {
            return $this->getAttributeValue($key);
        }

        return;
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
            return $this->getRelationshipFromMethod($key)->getResults();
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
                    '%s::%s must return a relationship instance, but "null" was returned. Was the "return" keyword used?',
                    static::class,
                    $method
                ));
            }

            throw new LogicException(sprintf(
                '%s::%s must return a relationship instance.',
                static::class,
                $method
            ));
        }
        $this->setRelation($method, $relation);

        return $this->getRelation($method);
//        return tap($relation->getResults(), function ($results) use ($method) {
//
//        });
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return $this->dateFormat ?: 'Y-m-d H:i:s';
    }
}
