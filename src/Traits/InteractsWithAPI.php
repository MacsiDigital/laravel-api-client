<?php

namespace MacsiDigital\API\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use MacsiDigital\API\Exceptions\InvalidActionException;
use MacsiDigital\API\Exceptions\ValidationFailedException;
use MacsiDigital\API\Support\Builder;

trait InteractsWithAPI
{
    use ForwardsCalls;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Indicates if the model was inserted during the current request lifecycle.
     *
     * @var bool
     */
    public $wasRecentlyCreated = false;

    // Allow not a RESTful verb we have 'find' for requests where the id is passed in the URL.
    protected $allowedMethods = ['find', 'get', 'post', 'patch', 'put', 'delete'];

    protected $endPoint = 'user';

    protected $customEndPoints = [];

    protected $createMethod = 'post';

    protected $updateMethod = 'patch';

    protected $insertResource;
    protected $updateResource;

    protected $primaryKey = 'id';

    // Most API's return data in a data attribute.  However we need to override on a model basis as some like Xero return it as 'Users' or 'Invoices'
    protected $apiDataField = 'data';

    // Also, some API's return 'users' for multiple and user for single, set teh multiple field below to wheat is required if different
    protected $apiMultipleDataField = 'data';

    protected $wrapInOnInsert = '';
    protected $wrapInEmptyArrayOnInsert = false;

    protected $wrapInOnUpdate = '';
    protected $wrapInEmptyArrayOnUpdate = false;

    public $passOnKeys = [];

    public function setPassOnAttributes(array $keys)
    {
        $this->passOnKeys = $keys;

        return $this;
    }

    public function hasPassOnAttributes()
    {
        return $this->passOnKeys != [];
    }

    public function passOnAttributes($item)
    {
        foreach ($this->passOnKeys as $key) {
            if (is_object($item)) {
                if (! isset($item->$key)) {
                    $item->$key = $this->$key;
                }
            } elseif (is_array($item)) {
                if (! isset($item[$key])) {
                    $item[$key] = $this->$key;
                }
            }
        }

        return $item;
    }

    public function query()
    {
        $class = $this->client->getBuilderClass();

        return new $class($this);
    }

    public function newQuery()
    {
        return $this->query($this);
    }

    public function getApiDataField()
    {
        return $this->apiDataField;
    }

    public function getApiMultipleDataField()
    {
        return $this->apiMultipleDataField;
    }

    public function getUpdateMethod()
    {
        return $this->updateMethod;
    }

    public function getCreateMethod()
    {
        return $this->createMethod;
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

    public function exists()
    {
        return $this->exists;
    }

    public function hasKey()
    {
        return $this->getKey() != null;
    }

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function getKeyForEndPoint()
    {
        if ($this->hasKey()) {
            return '/'.$this->getKey();
        }

        return;
    }

    public function setEndPoint($type, $endPoint)
    {
        $this->customEndPoints[$type] = $endPoint;
    }

    public function getEndPoint($type = 'get')
    {
        if ($this->canPerform($type)) {
            return $this->resolveBindings($this->{'get'.Str::studly($type).'EndPoint'}());
        } else {
            throw new InvalidActionException(sprintf(
                '%s action not allowed for %s',
                Str::studly($type),
                static::class
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

    protected function hasCustomEndPoint($type)
    {
        return isset($this->customEndPoints[$type]);
    }

    protected function getCustomEndPoint($type)
    {
        return $this->customEndPoints[$type];
    }

    public function getGetEndPoint()
    {
        if ($this->hasCustomEndPoint('get')) {
            return $this->getCustomEndPoint('get').$this->getKeyForEndPoint();
        }

        return $this->endPoint;
    }

    public function getFindEndPoint()
    {
        if ($this->hasCustomEndPoint('find')) {
            return $this->getCustomEndPoint('find').$this->getKeyForEndPoint();
        }

        return $this->endPoint;
    }

    public function getPostEndPoint()
    {
        if ($this->hasCustomEndPoint('post')) {
            return $this->getCustomEndPoint('post').$this->getKeyForEndPoint();
        }

        return $this->endPoint.$this->getKeyForEndPoint();
    }

    public function getPatchEndPoint()
    {
        if ($this->hasCustomEndPoint('patch')) {
            return $this->getCustomEndPoint('patch').$this->getKeyForEndPoint();
        }

        return $this->endPoint.$this->getKeyForEndPoint();
    }

    public function getPutEndPoint()
    {
        if ($this->hasCustomEndPoint('put')) {
            return $this->getCustomEndPoint('put').$this->getKeyForEndPoint();
        }

        return $this->endPoint.$this->getKeyForEndPoint();
    }

    public function getDeleteEndPoint()
    {
        if ($this->hasCustomEndPoint('delete')) {
            return $this->getCustomEndPoint('delete').$this->getKeyForEndPoint();
        }

        return $this->endPoint.$this->getKeyForEndPoint();
    }

    public function resolveBindings($url)
    {
        if (Str::contains($url, '{')) {
            $pattern = '/{\K[^}]*(?=})/m';
            $n = preg_match($pattern, $url, $matches);
            if ($n > 0) {
                foreach ($matches as $match) {
                    $url = Str::replaceFirst('{'.$match.'}', $this->{str_replace(':', '_', $match)}, $url);
                }
            }
        }

        return $url;
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
        if (! $this->hasKey()) {
            return false;
        }

        return $this->fill($attributes)->save($options);
    }

    /**
     * Save the model and all of its relationships.
     *
     * @return bool
     */
    public function push()
    {
        if (! $this->save()) {
            return false;
        }

        // To sync all of the relationships to the database, we will simply spin through
        // the relationships and save each model via this "push" method, which allows
        // us to recurse into all of these nested relations for the model instance.
        foreach ($this->relations as $models) {
            $models = $models instanceof Collection
                        ? $models->all() : [$models];

            foreach (array_filter($models) as $model) {
                if (! $model->push()) {
                    return false;
                }
            }
        }

        return true;
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
        if ($this->hasKey()) {
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
        $query = $this->newQuery();

        // If the "beforeSave" function returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->beforeSave($options, $query) === false) {
            return;
        }

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
        if (! $resource->hasApiError()) {
            $this->afterSave($options, $query);
        }

        return $resource;
    }

    public function hasApiError()
    {
        return isset($this->status_code);
    }

    /**
     * Perform any actions that are necessary after the model is saved.
     *
     * @param  array  $options
     * @return void
     */
    protected function finishSave(array $options)
    {
        $this->syncOriginal();
    }

    /**
     * Perform a model update operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        if ($this->beforeUpdate($query) === false) {
            return;
        }

        $resource = (new $this->updateResource)->fill($this, 'update');

        if ($resource->getAttributes() != []) {
            $validator = $resource->validate();

            if ($validator->fails()) {
                throw new ValidationFailedException($validator->errors());
            }

            $query->{$this->getUpdateMethod()}($resource->getAttributes($this->wrapInOnUpdate, $this->wrapInEmptyArrayOnUpdate));

            $this->syncChanges();

            $this->afterUpdate($query);

            return $this;
        } else {
            return $this;
        }
    }

    /**
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        if ($this->beforeInsert($query) === false) {
            return;
        }

        $resource = (new $this->insertResource)->fill($this, 'insert');

        $validator = $resource->validate();

        if ($validator->fails()) {
            throw new ValidationFailedException($validator->errors());
        }

        $query->{$this->getCreateMethod()}($resource->getAttributes($this->wrapInOnInsert, $this->wrapInEmptyArrayOnInsert));

        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->afterInsert($query);

        return $this;
    }

    protected function performCustomQuery($method, $attributes)
    {
        return $this->newQuery()->$method(...$attributes);
    }

    public function beforeSave($options, $query)
    {
        return $query;
    }

    public function afterSave($options, $query)
    {
    }

    public function beforeInsert($query)
    {
        return $query;
    }

    public function afterInsert($query)
    {
    }

    public function beforeUpdate($query)
    {
        return $query;
    }

    public function afterUpdate($query)
    {
    }

    /**
     * Destroy the models for the given IDs.
     *
     * @param  \Illuminate\Support\Collection|array|int  $ids
     * @return int
     */
    public static function destroy($ids)
    {
        // We'll initialize a count here so we will return the total number of deletes
        // for the operation. The developers can then check this number as a boolean
        // type value or get this total count of records deleted for logging, etc.
        $count = 0;

        if ($ids instanceof Collection) {
            $ids = $ids->all();
        }

        $ids = is_array($ids) ? $ids : func_get_args();

        // We will actually pull the models from the database table and call delete on
        // each of them individually so that their events get fired properly with a
        // correct set of attributes in case the developers wants to check these.
        $key = ($instance = (new static($this->client)))->getKeyName();

        foreach ($instance->whereIn($key, $ids)->get() as $model) {
            if ($model->delete()) {
                $count++;
            }
        }

        return $count;
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
        $query = $this->newQuery();

        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        if (! $this->hasKey()) {
            return;
        }

        $this->beforeDeleting($query);

        $response = $query->delete();

        if ($response) {
            $this->afterDeleting($query);
        }

        return $response;
    }

    public function beforeDeleting($query)
    {
        return $query;
    }

    public function afterDeleting($query)
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
        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forModel($this, json_last_error_msg());
        }

        return $json;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function fresh()
    {
        if (! ($this->exists())) {
            return;
        }

        if (method_exists($this, 'find')) {
            return $this->find($this->getKey());
        }

        return $this->newQuery()->find($this->getKey());
    }

    /**
     * Reload the current model instance with fresh attributes from the database.
     *
     * @return $this
     */
    public function refresh()
    {
        if (! ($this->exists())) {
            return $this;
        }

        $this->setRawAttributes(
            $this->fresh()->attributes
        );

        $this->syncOriginal();

        return $this;
    }

    /**
     * Clone the model into a new, non-existing instance.
     *
     * @param  array|null  $except
     * @return static
     */
    public function replicate(array $except = null)
    {
        $defaults = [
            $this->getKeyName(),
        ];

        $attributes = Arr::except(
            $this->attributes,
            $except ? array_unique(array_merge($except, $defaults)) : $defaults
        );

        return tap(new static($this->client), function ($instance) use ($attributes) {
            $instance->setRawAttributes($attributes);

            $instance->setRelations($this->relations);
        });
    }

    /**
     * Determine if two models have the same ID and belong to the same table.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     */
    public function is($model)
    {
        return ! is_null($model) &&
               get_class($this) === get_class($model) &&
               $this->getKey() === $model->getKey();
    }

    /**
     * Determine if two models are not the same.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     */
    public function isNot($model)
    {
        return ! $this->is($model);
    }

    public function beforeQuery($query)
    {
    }

    public function beforePostQuery($query)
    {
    }

    public function beforePatchQuery($query)
    {
    }

    public function beforePutQuery($query)
    {
    }

    public function beforeDeleteQuery($query)
    {
    }
}
