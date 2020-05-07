<?php

namespace MacsiDigital\API\Support\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class HasMany extends Relation
{
    protected $relation;

    protected function hydrate($data) 
    {
    	$collection = new Collection;
		foreach($data as $item){
			$collection->push($this->fill($item));
		}
    	$this->relation = $collection;
    }

    public function first() 
    {
    	return $this->relation->first();
    }

}