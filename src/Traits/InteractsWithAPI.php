<?php

namespace MacsiDigital\API\Traits;

use Illuminate\Support\Str;
use MacsiDigital\API\Support\Builder;
use Illuminate\Support\Traits\ForwardsCalls;
use MacsiDigital\API\Exceptions\ValidationFailedException;
use MacsiDigital\API\Exceptions\CantDeleteException;

trait InteractsWithAPI
{
    use ForwardsCalls;
    
    // index, create, show, update, delete
    // Allow all methods by default
    protected $allowedMethods = ['index', 'create', 'show', 'update', 'delete'];

    protected $endPoint = 'user';
    protected $updateMethod = 'patch';

    protected $storeResource;
    protected $updateResource;

    protected $primaryKey = 'id';

    protected $apiDataField = 'data';

    public static function query($resource)
    {
        return new Builder($resource);
    }

    public function newQuery() 
    {
        return self::query($this);
    }

    public function getApiDataField()
    {
        return $this->apiDataField;
    }

    public function getUpdateMethod()
    {
        return $this->updateMethod;
    }

    public function getKeyName()
    {
        return $this->primaryKey;
    }

    public function setKeyName($key)
    {
        $this->primaryKey = $key;

        return $this;
    }

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function exists()
    {
        return $this->getKey() != null;
    }

    public function getEndPoint($type='index') 
    {
        if($this->canPerform($type)){
            return $this->{'get'.Str::studly($type).'EndPoint'}();
        } else {
            throw new InvalidActionException(sprintf(
                '%s action not allowed for %s', Str::studly($type), static::class
            ));
        }
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

    public function getIndexEndPoint() 
    {
        return $this->endPoint;
    }

    public function getCreateEndPoint() 
    {
        return $this->endPoint;
    }

    public function getShowEndPoint() 
    {
        return $this->endPoint.'/';
    }

    public function getUpdateEndPoint() 
    {
        if($this->exists()){
            return $this->endPoint.'/'.$this->getKey();
        }
        throw new KeyNotFoundException(static::class);
    }

    public function getDeleteEndPoint() 
    {
        if($this->exists()){
            return $this->endPoint.'/'.$this->getKey();
        }
        throw new KeyNotFoundException(static::class);
    }

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (!$this->exists()) {
            return false;
        }

        return $this->fill($attributes)->save($options);
    }

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function create(array $attributes = [], array $options = [])
    {
        if ($this->exists()) {
            return false;
        }
        
        return $this->fill($attributes)->save($options);
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $this->mergeAttributesFromClassCasts();

        $query = $this->newQuery();

        $this->beforeSave($options);
        
        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists()) {
            $resource = $this->performUpdate($query);
        }
        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $resource = $this->performInsert($query);
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if (!$resource->hasApiError()) {
            $this->afterSave($options);
        }
        
        return $resource;
    }

    public function hasApiError() 
    {
        return isset($this->status_code);
    }

    /**
     * Perform a model update operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        $resource = (new $this->updateResource)->fill($this->package()->toArray());
        $validator = $resource->validate();

        if ($validator->fails()) {
            throw new ValidationFailedException($validator->errors());
        }

        return $query->{$this->getUpdateMethod()}($resource->getAttributes());
    }

    /**
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        $resource = (new $this->storeResource)->fill($this->package()->toArray());
        
        $validator = $resource->validate();

        if ($validator->fails()) {
            throw new ValidationFailedException($validator->errors());
        }

        return $query->post($resource->getAttributes());;
    }

    public function beforeSave()
    {
        
    }

    public function afterSave()
    {
        
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        $this->mergeAttributesFromClassCasts();

        $query = $this->newQuery();

        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        if (! $this->exists()) {
            return;
        }
        $this->beforeDeleting();

        $response = $query->delete();
        if($response->successful()){
            $this->afterDeleting();
        //} else {
            //throw new CantDeleteException(static::class, $this->getKey());
        }
        return $response;
    }

    public function beforeDeleting()
    {
        
    }

    public function afterDeleting()
    {
        
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return $this->$method(...$parameters);
        }
        
        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }

}