<?php

namespace MacsiDigital\API\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
                $this->addRelationToAttributes($key, $value);
            } elseif(Arr::exists($this->getPersistAttributes(), $key)){
                $this->addAttributeToAttributes($key, $value);
            } elseif(is_object($value)) {
                $this->addModelToAttributes($key, $value);
        	} elseif(is_array($value)) {
                $this->addArrayToAttributes($key, $value);
            }
        }
        return $this;
    }

    protected function addRelationToAttributes($key, $value) 
    {
        $resource = $this->getRelatedClass($key);
        $resource = new $resource();
        $resource->fill($value);
        $this->attributes[$key] = $resource->toArray();
    }

    public function addModelToAttributes($key, $value) 
    {
        foreach($this->getPersistAttributes() as $field => $validation){
            if(Str::contains($field, $key.'.')){
                $this->attributes[$key] = $this->processRecursive($key, $value->toArray());
            } elseif(Str::contains($field, Str::singular($key).'.')){
                $this->attributes[$key] = $this->processRecursive(Str::singular($key), $value->toArray());
            } elseif(Str::contains($field, Str::plural($key).'.')){
                $this->attributes[$key] = $this->processRecursive(Str::plural($key), $value->toArray());
            }
        }
    }

    public function addArrayToAttributes($key, $value) 
    {
        foreach($this->getPersistAttributes() as $field => $validation){
            if(Str::contains($field, $key.'.')){
                $this->attributes[$key] = $this->processRecursive($key, $value);
            } elseif(Str::contains($field, Str::singular($key).'.')){
                $this->attributes[Str::singular($key)] = $this->processRecursive(Str::singular($key), $value);
            } elseif(Str::contains($field, Str::plural($key).'.')){
                $this->attributes[Str::plural($key)] = $this->processRecursive(Str::plural($key), $value);
            }
        }
    }

    protected function addAttributeToAttributes($key, $value) 
    {
        if(is_array($value)){
           $this->attributes[$key] = $this->processArray($key, $value); 
        } elseif(is_object($value)) {
           $this->attributes[$key] = $this->processObject($key, $value); 
        } else {
           $this->attributes[$key] = $value;
        }
    }

    protected function processArray($key, $value)
    {
        if(is_object($value[0])){
            $array = $this->processObject($key, $value);
        } else {
            $array = $value;
        }
       return $array;
    }

    protected function processObject($key, $value)
    {
        $array = [];
        foreach($value as $object){
            $array[] = $object->toArray();
        }
        return $array;
    }

    public function processRecursive($key, $value) 
    {
        $array = [];
        foreach($value as $childKey => $childValue){
            if(Arr::exists($this->getPersistAttributes(), $key.'.'.$childKey)){
                $array[$childKey] = $childValue;
            } elseif(Str::contains($childKey, $key.'.') && is_array($childValue)){
                $array[$childKey] = $this->processRecursive($key.'.'.$childKey, $childValue);
            } elseif(is_array($childValue) && is_numeric($childKey)){
                $array[] = $this->processRecursive($key, $childValue);
            } elseif(is_object($childValue) && is_numeric($childKey)){
                $array[] = $this->processRecursive($key, $childValue->toArray());
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
}