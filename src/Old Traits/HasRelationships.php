<?php

namespace MacsiDigital\Stripe\Traits;

use Illuminate\Support\Str;

trait HasRelationships
{
	protected $oneRelationships = [];

	protected $manyRelationships = [];

	protected $loadedRelationships = [];

	 /**
     * Get the model's relationships in array form.
     *
     * @return array
     */
    public function relationsToArray()
    {
        $attributes = [];

        foreach ($this->getArrayableRelations() as $key => $value) {
            // If the values implements the Arrayable interface we can just call this
            // toArray method on the instances which will convert both models and
            // collections to their proper array form and we'll set the values.
            if ($value instanceof Arrayable) {
                $relation = $value->toArray();
            }

            // If the value is null, we'll still go ahead and set it in this list of
            // attributes since null is used to represent empty relationships if
            // if it a has one or belongs to type relationships on the models.
            elseif (is_null($value)) {
                $relation = $value;
            }

            // If the relationships snake-casing is enabled, we will snake case this
            // key so that the relation attribute is snake cased in this returned
            // array to the developers, making this consistent with attributes.
            if (static::$snakeAttributes) {
                $key = Str::snake($key);
            }

            // If the relation value has been set, we will set it on this attributes
            // list for returning. If it was not arrayable or null, we'll not set
            // the value on the array because it is some type of invalid value.
            if (isset($relation) || is_null($value)) {
                $attributes[$key] = $relation;
            }

            unset($relation);
        }

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable relations.
     *
     * @return array
     */
    protected function getArrayableRelations()
    {
        return $this->getArrayableItems(array_merge($this->oneRelationships, $this->manyRelationships));
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
            return $this->getRelationship($key);
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
        return $this->$method()->get();
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
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
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
    	if($this->isRelationshipAttribute($key)){
    		$this->attributes[$key] = $this->processRelationship($key, $value);	
    	 	$this->relationshipLoaded($key, $this->attributes[$key]);
    	 	return;
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

        return $this;
    }

    public function isRelationshipAttribute($key) 
    {
    	return array_key_exists($key, array_merge($this->oneRelationships, $this->manyRelationships));
    }

    public function processRelationship($key, $value) 
    {
    	if($value == null){
    		return $value;
    	}
    	if($this->isOneRelationship($key)){
    		return $this->processOneRelationship($key, $value);
    	} else if($this->isManyRelationship($key)){
    		return $this->processManyRelationship($key, $value);
    	}
    }

    public function isOneRelationship($key) 
    {
    	return array_key_exists($key, $this->oneRelationships);
    }

    public function isManyRelationship($key) 
    {
    	return array_key_exists($key, $this->manyRelationships);	
    }

    public function processOneRelationship($key, $value) 
    {
    	$class = $this->oneRelationships[$key];
    	return new $class($value);
    }

    public function processManyRelationship($key, $value) 
    {
    	$class = $this->manyRelationships[$key];
    	$objects = [];
    	foreach($value['data'] as $data){
    		$objects[] = new $class(array_merge($data, ['StripeObject' => $data]));
    	}
    	return collect($objects);
    }

    public function relationshipLoaded($key, $value) 
    {
    	$this->loadedRelationships[$key] = $value;
    }

    public function relationLoaded($key){
    	return array_key_exists($key, $this->loadedRelationships);
    }

    public function getRelationship($key){
    	return $this->loadedRelationships[$key];	
    }

}