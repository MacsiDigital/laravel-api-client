<?php

namespace MacsiDigital\API\Support;

use Illuminate\Support\Facades\Validator;
use MacsiDigital\API\Traits\HasAttributes;

class StoreResource
{
    use HasAttributes;

    protected $storeAttributes;

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
        	if(array_key_exists($key, $this->getStoreAttributeKeys())){
            	$this->setAttribute($key, $value);
        	}
        }

        return $this;
    }

    public function getValidationAttributes()
    {
    	return $this->storeAttributes;
    }

    public function getStoreAttributeKeys()
    {
    	return array_keys($this->storeAttributes);
    }

    public function validate()
    {
    	return Validator::make($this->getArrayableAttributes(), $this->getValidationAttributes());
    }
}