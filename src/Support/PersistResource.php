<?php

namespace MacsiDigital\API\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class PersistResource
{

    protected $persistAttributes = [];
    protected $relatedResource = [];

    protected $attributes = [];

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if(Arr::exists($this->getRelatedResources(), $key)){
                $resource = $this->getRelatedClass($key);
                $resource = new $resource();
                $resource->fill($value);
                $this->attributes[$key] = $resource->getAttributes();
            }
            if(Arr::exists($this->getPersistAttributes(), $key)){
            	$this->attributes[$key] = $value;
        	} elseif(is_array($value)) {
                $this->attributes[$key] = $this->recursiveFill($key, $value);
            }
        }

        return $this;
    }

    public function recursiveFill($key, $value) 
    {
        $array = [];
        foreach($value as $childKey => $childValue){
            if(Arr::exists($this->getPersistAttributes(), $key.'.'.$childKey)){
                $array[$childKey] = $childValue;
            } elseif(is_array($childValue)){
                $array[$childKey] = $this->recursiveFill($key.'.'.$childKey, $childValue);
            }
        }
        return $array;
    }

    public function getValidationAttributes()
    {
    	return $this->persistAttributes;
    }

    public function getPersistAttributes()
    {
    	return $this->persistAttributes;
    }

    public function getRelatedResources()
    {
        return $this->relatedResource;
    }

    public function getRelatedClass($key)
    {
        return $this->relatedResource[$key];
    }

    public function getAttributes() 
    {
        return $this->attributes;
    }

    public function validate()
    {
    	return Validator::make($this->getAttributes(), $this->getValidationAttributes());
    }

    public function updateRelatedResource() 
    {
        
    }
}