<?php

namespace MacsiDigital\Stripe\Traits;

use MacsiDigital\Stripe\Exceptions\ValidationFailedException;

trait InteractsWithAPI
{

    protected $insertAttributes = [];

    protected $updateAttributes = [];

    protected $queryAttributes = [];

    protected $requiredAttributes = [];

    protected $allowedMethods = [];

    protected $allowsOrdering = false;

    /**
     * Get the attributes to pass to the API to update for insert
     *
     * @return array
     */
    public function getInsertAttributes()
    {
        $attributes = [];
        foreach($this->attributes as $key => $value){
        	if($this->isInsertAttribute($key)){
	            $attributes[$key] = $value;
	        }	
        }
        if(!$this->validateAttributes($attributes)){
        	throw new ValidationFailedException (get_class($this).' failed validation');
        }
        return $attributes;
    }

    public function canPerform($type) 
    {
    	return in_array($type, $this->getAllowedMethods());
    }

    public function cantPerform($type) 
    {
    	return ! $this->canPerform($type);
    }

    public function getAllowedMethods() 
    {
    	return $this->allowedMethods;
    }

    /**
     * Get the attributes to pass to the API to update for update
     *
     * @return array
     */
    public function getUpdateAttributes()
    {
        $dirty = [];
        foreach($this->getDirty() as $key => $value){
            if($this->isUpdateAttribute($key)){
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    protected function isUpdateAttribute($key) 
    {
        return in_array($key, $this->updateAttributes);
    }

    protected function isInsertAttribute($key) 
    {
        return in_array($key, $this->insertAttributes);
    }

    protected function isQueryAttribute($key) 
    {
        return array_key_exists($key, $this->queryAttributes);
    }

    protected function validateAttributes($attributes)
    {
    	$validated = true;
    	foreach($this->requiredAttributes as $key){
    		if(!array_key_exists($key, $attributes)){
    			$validated = false;
    		}
    	}

    	return $validated;
    }

}