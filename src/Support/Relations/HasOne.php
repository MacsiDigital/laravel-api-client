<?php

namespace MacsiDigital\API\Support\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class HasOne extends Relation
{
    protected $relation;

    protected function hydrate($data) 
    {
    	if($data != []){
    		$this->relation = $this->fill($data);
    	} else {
    		$this->relation = $this->related->fresh();
    	} 
    }

}