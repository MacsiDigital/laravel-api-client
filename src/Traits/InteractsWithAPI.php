<?php

namespace MacsiDigital\API\Traits;

use Illuminate\Support\Str;
use MacsiDigital\API\Support\Builder;

trait InteractsWithAPI
{
    // index, create, show, update, delete
    // Allow all methods by default
    protected $allowedMethods = ['index', 'create', 'show', 'update', 'delete'];
    protected $endPoint = 'user';

    protected $storeResource;
    protected $updateResource;

    protected $primaryKey = 'id';

    public static function query($resource)
    {
        return new Builder($resource);
    }

    public function newQuery() 
    {
        return self::query($this);
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
            return $this->{'get'.Str::studly($type).'endPoint'}();
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

    public function getInsertEndPoint() 
    {
        return $this->endPoint;
    }

    public function getShowEndPoint() 
    {
        return $this->endPoint.'/';
    }

    public function getUpdateEndPoint() 
    {
        return $this->endPoint.'/';
    }

    public function getDeleteEndPoint() 
    {
        return $this->endPoint.'/';
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
        if ($this->exists()) {
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
        if (!$this->exists()) {
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
            $saved = $this->performUpdate($query);
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->performInsert($query);
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if ($saved) {
            $this->afterSave($options);
        }

        return $saved;
    }

    /**
     * Perform a model update operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        $resource = (new $this->updateResource)->fill($this->getAttributes());

        $validation = $resource->validate();

        if ($validator->fails()) {
            throw new ValidationFailedExcpetion($validator);
        }

        $query->update($resource->toArray());

        return true;
    }

    /**
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        $resource = (new $this->storeResource)->fill($this->getAttributes());

        $validation = $resource->validate();

        if ($validator->fails()) {
            throw new ValidationFailedExcpetion($validator);
        }

        $query->insert($resource->toArray());

        return true;
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

        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        // If the model doesn't exist, there is nothing to delete so we'll just return
        // immediately and not do anything else. Otherwise, we will continue with a
        // deletion process on the model, firing the proper events, and so forth.
        if (! $this->exists) {
            return;
        }

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        // Here, we'll touch the owning models, verifying these timestamps get updated
        // for the models. This will allow any caching to get broken on the parents
        // by the timestamp. Then we will go ahead and delete the model instance.
        $this->touchOwners();

        $this->performDeleteOnModel();

        // Once the model has been deleted, we will fire off the deleted event so that
        // the developers may hook into post-delete operations. We will then return
        // a boolean true as the delete is presumably successful on the database.
        $this->fireModelEvent('deleted', false);

        return true;
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