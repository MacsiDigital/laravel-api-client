<?php

namespace MacsiDigital\API\Traits;

use Illuminate\Database\Eloquent\Concerns\HasAttributes as LaravelHasAttributes;
use MacsiDigital\API\Contracts\Relation;

trait HasAttributes
{
    use LaravelHasAttributes;

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
}