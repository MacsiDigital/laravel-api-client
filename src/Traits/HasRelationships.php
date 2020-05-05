<?php

namespace MacsiDigital\API\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use MacsiDigital\API\Relations\HasOne;

trait HasRelationships
{
    
    protected $relations = [];

    protected $results = [];

    // List of relations to load on creation
    protected $dontAutoloadRelation = [];

    protected $loadRaw = false;

    protected function resolveRelationName($name) 
    {
        if($name == ''){
            $name = debug_backtrace()[2]['function'];
        }
        return $name;
    }

    protected function resolveRelationField($field, $name) 
    {
        if($field == ''){
            $field = $name.'_id';
        }
        return $field;
    }

    public function hasOne($related, $name="", $field="")
    {
        $name = $this->resolveRelationName($name);
        if(!$this->relationLoaded($name)){
            $field = $this->resolveRelationField($field, $this->getModelName());
            $this->setRelation($name, new HasOne($related, $this, $name, $field));
        }
        return $this->getRelation($name);
    }

    public function belongsTo($related, $name="")
    {
        $name = $this->resolveRelationName($name);
        if(!$this->relationLoaded($name)){
            $field = $this->resolveRelationField($field, $name);
            $this->setRelation($name, new BelongsTo($related, $this, $name, $field));
        }
        return $this->getRelation($name);
    }

    public function hasMany($related, $name="")
    {
        $name = $this->resolveRelationName($name);
        if(!$this->relationLoaded($name)){
            $field = $this->resolveRelationField($field, $name);
            $this->setRelation($name, new HasMany($related, $this, $name, $field));
        }
        return $this->getRelation($name);
    }

    public function belongsToMany($related, $name="")
    {
        $name = $this->resolveRelationName($name);
        if(!$this->relationLoaded($name)){
            $field = $this->resolveRelationField($field, $name);
            $this->setRelation($name, new BelongsToMany($related, $this, $name, $field));
        }
        return $this->getRelation($name);
    }

    /**
     * Get all the loaded relations for the instance.
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get a specified relationship.
     *
     * @param  string  $relation
     * @return mixed
     */
    public function getRelation($relation)
    {
        return $this->relations[$relation];
    }

    /**
     * Determine if the given relation is loaded.
     *
     * @param  string  $key
     * @return bool
     */
    public function relationLoaded($key)
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * Set the given relationship on the model.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Unset a loaded relationship.
     *
     * @param  string  $relation
     * @return $this
     */
    public function unsetRelation($relation)
    {
        unset($this->relations[$relation]);

        return $this;
    }

    /**
     * Set the entire relations array on the model.
     *
     * @param  array  $relations
     * @return $this
     */
    public function setRelations(array $relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * Duplicate the instance and unset all the loaded relations.
     *
     * @return $this
     */
    public function withoutRelations()
    {
        $model = clone $this;

        return $model->unsetRelations();
    }

    /**
     * Unset all the loaded relations for the instance.
     *
     * @return $this
     */
    public function unsetRelations()
    {
        $this->relations = [];

        return $this;
    }

    public function getModelName() 
    {
        $segments = explode('\\', static::class);
        return strtolower(end($segments));
    }

    public function isRelationship($key) 
    {
        return method_exists($this, $key);
    }

}